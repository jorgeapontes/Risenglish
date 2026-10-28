<?php
/**
 * Regras comuns dos uploads (materiais dos professores e anexos de usuários), para o espaço
 * em disco não crescer à toa:
 *   1. Limite de tamanho por tipo: PDF até 40 MB, imagem até 10 MB, Word/Excel e outros documentos até 50 MB.
 *   2. Imagens JPG/PNG são redimensionadas (lado maior até 2000 px) e recomprimidas, mantendo
 *      o formato — só substitui o original se ficar menor. GIF fica como está (pode ser animado).
 *   3. Arquivo idêntico a um que já está na mesma pasta é reaproveitado: o registro novo aponta
 *      para o arquivo existente e a cópia é descartada. Por isso, antes de apagar um arquivo,
 *      confira com upload_arquivo_em_uso() se nenhum outro registro ainda usa o mesmo caminho.
 */
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/avatar.php'; // avatar_corrigir_orientacao()

const UPLOAD_LIMITE_PDF = 40 * 1024 * 1024;
const UPLOAD_LIMITE_IMAGEM = 10 * 1024 * 1024;
const UPLOAD_LIMITE_OUTROS = 50 * 1024 * 1024; // Word, Excel e outros documentos
const UPLOAD_IMAGEM_LADO_MAX = 2000;

/** Limite em bytes para a extensão do arquivo. */
function upload_limite_bytes(string $extensao): int
{
    $extensao = strtolower($extensao);
    if ($extensao === 'pdf') {
        return UPLOAD_LIMITE_PDF;
    }
    if (in_array($extensao, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        return UPLOAD_LIMITE_IMAGEM;
    }
    return UPLOAD_LIMITE_OUTROS;
}

/** Mensagem pronta quando o arquivo passa do limite (ou null se está dentro). */
function upload_erro_tamanho(string $nomeOriginal, int $bytes): ?string
{
    $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
    $limite = upload_limite_bytes($extensao);
    if ($bytes <= $limite) {
        return null;
    }
    $mb = fn ($b) => number_format($b / 1048576, 1, ',', '.');
    $dica = $extensao === 'pdf' ? ' Dica: comprima o PDF em ilovepdf.com/pt/comprimir_pdf antes de enviar.' : '';
    return "\"{$nomeOriginal}\" tem {$mb($bytes)} MB; o limite para esse tipo de arquivo é " . round($limite / 1048576) . " MB.{$dica}";
}

/**
 * Reduz uma imagem JPG/PNG já gravada em $caminho. Mantém o formato e só troca o arquivo
 * se o resultado ficar menor. Qualquer falha deixa o original intacto.
 */
function upload_otimizar_imagem(string $caminho): void
{
    $extensao = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
    if (!in_array($extensao, ['jpg', 'jpeg', 'png'], true) || !function_exists('imagecreatefromstring')) {
        return;
    }
    try {
        $info = @getimagesize($caminho);
        if (!$info || $info[0] * $info[1] > AVATAR_MAX_PIXELS) {
            return; // não é imagem válida ou é grande demais para processar com segurança
        }
        $imagem = $extensao === 'png' ? @imagecreatefrompng($caminho) : @imagecreatefromjpeg($caminho);
        if (!$imagem) {
            return;
        }
        if ($extensao !== 'png') {
            $imagem = avatar_corrigir_orientacao($imagem, $caminho);
        }

        $largura = imagesx($imagem);
        $altura = imagesy($imagem);
        $escala = min(1, UPLOAD_IMAGEM_LADO_MAX / max($largura, $altura));
        if ($escala < 1) {
            $novaLargura = (int) round($largura * $escala);
            $novaAltura = (int) round($altura * $escala);
            $redimensionada = imagecreatetruecolor($novaLargura, $novaAltura);
            if ($extensao === 'png') {
                imagealphablending($redimensionada, false);
                imagesavealpha($redimensionada, true);
            }
            imagecopyresampled($redimensionada, $imagem, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);
            imagedestroy($imagem);
            $imagem = $redimensionada;
        } elseif ($extensao === 'png') {
            imagesavealpha($imagem, true);
        }

        $temporario = $caminho . '.otimizando';
        $ok = $extensao === 'png' ? imagepng($imagem, $temporario, 9) : imagejpeg($imagem, $temporario, 82);
        imagedestroy($imagem);
        if ($ok && is_file($temporario) && filesize($temporario) > 0 && filesize($temporario) < filesize($caminho)) {
            rename($temporario, $caminho);
        } else {
            @unlink($temporario);
        }
    } catch (Throwable $e) {
        error_log('Otimização de imagem (' . basename($caminho) . '): ' . $e->getMessage());
        @unlink($caminho . '.otimizando');
    }
}

/**
 * Se já existir na mesma pasta um arquivo idêntico ao recém-gravado em $caminho, apaga a cópia
 * e devolve o nome do arquivo existente. Senão devolve o próprio nome. Compara tamanho e depois
 * o conteúdo (SHA-1), só entre arquivos com a mesma extensão.
 */
function upload_reaproveitar_identico(string $caminho): string
{
    $pasta = dirname($caminho);
    $nome = basename($caminho);
    $extensao = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
    $tamanho = filesize($caminho);
    $hash = null;
    foreach (new DirectoryIterator($pasta) as $outro) {
        if (!$outro->isFile() || $outro->getFilename() === $nome || $outro->getFilename()[0] === '.'
            || strtolower($outro->getExtension()) !== $extensao || $outro->getSize() !== $tamanho) {
            continue;
        }
        $hash = $hash ?? sha1_file($caminho);
        if (sha1_file($outro->getPathname()) === $hash) {
            @unlink($caminho);
            return $outro->getFilename();
        }
    }
    return $nome;
}

/** Quantos registros (materiais ou anexos) ainda apontam para este caminho (ex.: uploads/conteudos/x.pdf). */
function upload_arquivo_em_uso(string $caminhoBanco): bool
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT (SELECT COUNT(*) FROM conteudos WHERE caminho_arquivo = ?) + (SELECT COUNT(*) FROM usuarios_anexos WHERE caminho_arquivo = ?)");
    $stmt->execute([$caminhoBanco, $caminhoBanco]);
    return (int) $stmt->fetchColumn() > 0;
}

/** Apaga o arquivo físico só se nenhum registro o usa mais. $base é a pasta p/ (onde ficam os uploads). */
function upload_apagar_se_sem_uso(string $caminhoBanco, string $base): void
{
    if ($caminhoBanco === '' || strpos($caminhoBanco, '..') !== false || strpos($caminhoBanco, 'uploads/') !== 0) {
        return;
    }
    $arquivo = rtrim($base, '/') . '/' . $caminhoBanco;
    if (is_file($arquivo) && !upload_arquivo_em_uso($caminhoBanco)) {
        @unlink($arquivo);
    }
}

/** Script de navegador que avisa antes do envio quando um arquivo passa do limite (mesmas regras do servidor). */
function upload_script_aviso(): string
{
    $limites = json_encode(['pdf' => UPLOAD_LIMITE_PDF, 'imagem' => UPLOAD_LIMITE_IMAGEM, 'outros' => UPLOAD_LIMITE_OUTROS]);
    return <<<HTML
<script>
// Avisa na hora de escolher o arquivo, antes de esperar o envio, se ele passa do limite
document.addEventListener('change', function (e) {
    var campo = e.target;
    if (!campo || campo.type !== 'file' || !campo.files) return;
    var limites = {$limites};
    var problemas = [];
    Array.prototype.forEach.call(campo.files, function (arquivo) {
        var ext = (arquivo.name.split('.').pop() || '').toLowerCase();
        var tipo = ext === 'pdf' ? 'pdf' : (['jpg', 'jpeg', 'png', 'gif', 'webp'].indexOf(ext) >= 0 ? 'imagem' : 'outros');
        if (arquivo.size > limites[tipo]) {
            problemas.push('"' + arquivo.name + '" tem ' + (arquivo.size / 1048576).toFixed(1).replace('.', ',') + ' MB (limite: ' + Math.round(limites[tipo] / 1048576) + ' MB)');
        }
    });
    if (problemas.length) {
        alert('Arquivo grande demais:\\n\\n' + problemas.join('\\n') + (problemas.some(function (p) { return /\\.pdf"/i.test(p); }) ? '\\n\\nDica: comprima o PDF em ilovepdf.com/pt/comprimir_pdf antes de enviar.' : ''));
        campo.value = '';
    }
});
</script>
HTML;
}
