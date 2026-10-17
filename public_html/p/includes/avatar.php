<?php
/**
 * Fotos de perfil (avatares) de alunos e professores.
 *
 * A foto original nunca é guardada: no upload ela é recortada em quadrado,
 * reprocessada pelo GD (o que descarta EXIF/GPS e qualquer conteúdo embutido)
 * e salva em WebP em dois tamanhos:
 *   uploads/avatars/avatar_{base}_96.webp   miniaturas (listas, comentários)
 *   uploads/avatars/avatar_{base}_256.webp  sidebar e página de perfil
 *
 * Em usuarios.foto_perfil fica só a {base} ("{id}_{timestamp}"). Como o nome muda
 * a cada troca de foto, o navegador pode cachear os arquivos por tempo indefinido.
 */

const AVATAR_TAMANHOS = [96, 256];
const AVATAR_MAX_BYTES = 5 * 1024 * 1024;
const AVATAR_MAX_PIXELS = 30000000; // ~30 MP, evita estourar a memória do GD

function avatar_dir(): string
{
    return dirname(__DIR__, 2) . '/uploads/avatars';
}

/** Garante que a base vinda do banco tem o formato gerado por avatar_processar_upload(). */
function avatar_base_valida(?string $foto): bool
{
    return $foto !== null && preg_match('/^\d+_\d+$/', $foto) === 1;
}

/**
 * URL do avatar. $prefixo é o caminho relativo da página até public_html
 * (padrão: páginas em p/aluno/ e p/professor/).
 */
function avatar_url(?string $foto, int $tamanho = 96, string $prefixo = '../../'): ?string
{
    if (!avatar_base_valida($foto)) {
        return null;
    }
    $tamanho = $tamanho > 96 ? 256 : 96;
    return $prefixo . 'uploads/avatars/avatar_' . $foto . '_' . $tamanho . '.webp';
}

function avatar_iniciais(string $nome): string
{
    $partes = preg_split('/\s+/u', trim($nome), -1, PREG_SPLIT_NO_EMPTY);
    if (!$partes) {
        return '?';
    }
    $iniciais = mb_substr($partes[0], 0, 1);
    if (count($partes) > 1) {
        $iniciais .= mb_substr(end($partes), 0, 1);
    }
    return mb_strtoupper($iniciais);
}

/**
 * HTML do avatar: a foto, ou um círculo cinza claro com as iniciais (cores em css/avatar.css).
 * $tamanho é o tamanho exibido em px.
 */
function avatar_html(string $nome, ?string $foto, int $tamanho = 40, string $classes = '', string $prefixo = '../../'): string
{
    $classes = trim('avatar ' . $classes);
    $estilo = 'width:' . $tamanho . 'px;height:' . $tamanho . 'px;';
    $url = avatar_url($foto, $tamanho > 48 ? 256 : 96, $prefixo);

    if ($url !== null) {
        return '<img src="' . htmlspecialchars($url) . '" alt="' . htmlspecialchars($nome) . '"'
            . ' class="' . htmlspecialchars($classes) . '" width="' . $tamanho . '" height="' . $tamanho . '"'
            . ' style="' . $estilo . '" loading="lazy" decoding="async">';
    }

    $estilo .= 'font-size:' . round($tamanho * 0.4) . 'px;';
    return '<span class="' . htmlspecialchars($classes . ' avatar-iniciais') . '" style="' . $estilo . '"'
        . ' role="img" aria-label="' . htmlspecialchars($nome) . '">' . htmlspecialchars(avatar_iniciais($nome)) . '</span>';
}

/**
 * Avatar pequeno seguido do nome, para listas e tabelas.
 * $nomeHtml permite passar o nome já escapado/formatado (ex: com destaque de pesquisa);
 * se omitido, o nome é escapado aqui.
 */
function avatar_com_nome(string $nome, ?string $foto, int $tamanho = 28, ?string $nomeHtml = null, string $prefixo = '../../'): string
{
    return '<span class="avatar-nome">' . avatar_html($nome, $foto, $tamanho, '', $prefixo)
        . '<span>' . ($nomeHtml ?? htmlspecialchars($nome)) . '</span></span>';
}

/**
 * Foto do usuário logado, guardada na sessão para não consultar o banco a cada página.
 * Sessões abertas antes desta funcionalidade carregam o valor na primeira chamada.
 */
function avatar_foto_sessao(?PDO $pdo): ?string
{
    if (array_key_exists('user_foto', $_SESSION)) {
        return $_SESSION['user_foto'];
    }
    if ($pdo === null || empty($_SESSION['user_id'])) {
        return null;
    }
    try {
        $stmt = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $_SESSION['user_foto'] = $stmt->fetchColumn() ?: null;
    } catch (PDOException $e) {
        error_log('Erro ao buscar foto de perfil: ' . $e->getMessage());
        return null;
    }
    return $_SESSION['user_foto'];
}

function avatar_apagar(?string $foto): void
{
    if (!avatar_base_valida($foto)) {
        return;
    }
    foreach (AVATAR_TAMANHOS as $tamanho) {
        $arquivo = avatar_dir() . '/avatar_' . $foto . '_' . $tamanho . '.webp';
        if (is_file($arquivo)) {
            @unlink($arquivo);
        }
    }
}

/** Cria a pasta de avatares com um .htaccess que impede execução de scripts. */
function avatar_preparar_dir(): bool
{
    $dir = avatar_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }
    $htaccess = $dir . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, "# Somente imagens WebP geradas pelo site\n"
            . "Options -Indexes -ExecCGI\n"
            . "RemoveHandler .php .phtml .phar\n"
            . "RemoveType .php .phtml .phar\n"
            . "<FilesMatch \"\\.(?i:php\\d?|phtml|phar|pl|py|cgi|sh)$\">\n"
            . "    Require all denied\n"
            . "</FilesMatch>\n"
            . "<IfModule mod_headers.c>\n"
            . "    Header set Cache-Control \"public, max-age=31536000, immutable\"\n"
            . "</IfModule>\n");
    }
    return true;
}

/** Corrige a rotação de fotos de celular, que vem só no EXIF. */
function avatar_corrigir_orientacao($imagem, string $caminho)
{
    if (!function_exists('exif_read_data')) {
        return $imagem;
    }
    $exif = @exif_read_data($caminho);
    $orientacao = (int) ($exif['Orientation'] ?? 1);
    $angulos = [3 => 180, 6 => -90, 8 => 90];
    if (isset($angulos[$orientacao])) {
        $rotacionada = imagerotate($imagem, $angulos[$orientacao], 0);
        if ($rotacionada !== false) {
            return $rotacionada;
        }
    }
    return $imagem;
}

/**
 * Valida e processa o upload ($_FILES['foto']).
 * Retorna ['ok' => true, 'foto' => base] ou ['ok' => false, 'erro' => mensagem].
 */
function avatar_processar_upload(array $arquivo, int $usuarioId): array
{
    $erro = $arquivo['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'erro' => 'A imagem é muito grande. Envie uma foto de até 5 MB.'];
    }
    if ($erro !== UPLOAD_ERR_OK || !is_uploaded_file($arquivo['tmp_name'])) {
        return ['ok' => false, 'erro' => 'Não foi possível receber a imagem. Tente novamente.'];
    }
    if ($arquivo['size'] > AVATAR_MAX_BYTES) {
        return ['ok' => false, 'erro' => 'A imagem é muito grande. Envie uma foto de até 5 MB.'];
    }

    $caminho = $arquivo['tmp_name'];
    $info = @getimagesize($caminho);
    $tiposAceitos = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
    if ($info === false || !in_array($info[2], $tiposAceitos, true)) {
        return ['ok' => false, 'erro' => 'Formato não suportado. Envie uma imagem JPG, PNG ou WebP.'];
    }
    [$largura, $altura] = $info;
    if ($largura < 1 || $altura < 1 || $largura * $altura > AVATAR_MAX_PIXELS) {
        return ['ok' => false, 'erro' => 'A resolução da imagem é grande demais.'];
    }

    @ini_set('memory_limit', '256M');
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $origem = @imagecreatefromjpeg($caminho);
            if ($origem) {
                $origem = avatar_corrigir_orientacao($origem, $caminho);
            }
            break;
        case IMAGETYPE_PNG:
            $origem = @imagecreatefrompng($caminho);
            break;
        default:
            $origem = @imagecreatefromwebp($caminho);
    }
    if (!$origem) {
        return ['ok' => false, 'erro' => 'Não foi possível ler a imagem. Tente outro arquivo.'];
    }

    if (!avatar_preparar_dir()) {
        return ['ok' => false, 'erro' => 'Erro ao salvar a foto. Tente novamente mais tarde.'];
    }

    // Recorte quadrado central
    $largura = imagesx($origem);
    $altura = imagesy($origem);
    $lado = min($largura, $altura);
    $x = (int) (($largura - $lado) / 2);
    $y = (int) (($altura - $lado) / 2);

    $base = $usuarioId . '_' . time();
    foreach (AVATAR_TAMANHOS as $tamanho) {
        $destino = imagecreatetruecolor($tamanho, $tamanho);
        imagealphablending($destino, false);
        imagesavealpha($destino, true);
        imagefill($destino, 0, 0, imagecolorallocatealpha($destino, 0, 0, 0, 127));
        imagecopyresampled($destino, $origem, 0, 0, $x, $y, $tamanho, $tamanho, $lado, $lado);
        $salvo = imagewebp($destino, avatar_dir() . '/avatar_' . $base . '_' . $tamanho . '.webp', 80);
        if (!$salvo) {
            avatar_apagar($base);
            return ['ok' => false, 'erro' => 'Erro ao salvar a foto. Tente novamente mais tarde.'];
        }
    }

    return ['ok' => true, 'foto' => $base];
}
