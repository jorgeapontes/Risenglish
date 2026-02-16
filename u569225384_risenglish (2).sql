-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 16/02/2026 às 14:40
-- Versão do servidor: 11.8.3-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u569225384_risenglish`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `alunos_turmas`
--

CREATE TABLE `alunos_turmas` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `turma_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `alunos_turmas`
--

INSERT INTO `alunos_turmas` (`id`, `aluno_id`, `turma_id`) VALUES
(30, 18, 11),
(29, 23, 10),
(33, 24, 12),
(32, 25, 12),
(34, 26, 13),
(36, 27, 19),
(35, 28, 19),
(37, 29, 18),
(38, 30, 15),
(39, 31, 21),
(42, 32, 24),
(43, 33, 24),
(44, 34, 24),
(47, 35, 20),
(46, 36, 20),
(54, 37, 29),
(40, 38, 14),
(41, 39, 14),
(45, 40, 23),
(52, 41, 31),
(48, 42, 22),
(50, 43, 28),
(49, 44, 28),
(51, 45, 27),
(53, 46, 16),
(57, 47, 33),
(56, 48, 30),
(55, 49, 30),
(58, 52, 34),
(59, 53, 35),
(60, 54, 36),
(61, 55, 26),
(62, 56, 37),
(63, 57, 38);

-- --------------------------------------------------------

--
-- Estrutura para tabela `anotacoes_aula`
--

CREATE TABLE `anotacoes_aula` (
  `id` int(11) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `conteudo` text NOT NULL,
  `comentario_professor` text DEFAULT NULL,
  `visto` tinyint(1) NOT NULL DEFAULT 0,
  `data_visto` timestamp NULL DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `anotacoes_aula`
--

INSERT INTO `anotacoes_aula` (`id`, `aula_id`, `aluno_id`, `conteudo`, `comentario_professor`, `visto`, `data_visto`, `data_criacao`, `data_atualizacao`) VALUES
(1, 60, 18, 'teste', NULL, 0, NULL, '2025-12-19 10:23:58', '2025-12-19 10:23:58'),
(2, 133, 26, '', 'Bru, dont forget to enjoy the holidays', 0, NULL, '2025-12-19 18:14:46', '2025-12-19 18:14:46'),
(3, 233, 46, 'fireworks\r\non the sidewalk\r\ncurb\r\nshort way\r\nshe crashed\r\nthirty-one / thirty-first\r\nmy aunt /ent/\r\n\r\n\r\n1st first\r\ntwenty-first\r\ntwenty-second\r\nthird\r\nfourth - tenth\r\nninth\r\n29 twenty-ninth\r\nfourth\r\nsixth\r\n2025\r\nnew / released\r\nfor who\r\nBruna asked\r\nall channels\r\nHistory channel\r\n\r\nsecond last\r\nreferee - juiz\r\nrefe RÍ\r\neach time - cada tempo\r\nbreaks', NULL, 0, NULL, '2026-01-13 17:57:15', '2026-01-13 17:57:15'),
(4, 197, 26, 'lose Weight\r\non my cellphone\r\nend of the year\r\nHolidays\r\nreceiving visits\r\n\r\nTeacher Laura 18:11\r\nuseless\r\ndid you go to the beach?\r\ncheap\r\n\r\nTeacher Laura 18:17\r\nsea /si/ = see\r\nsave Money\r\nfor the entrance\r\ncozy\r\ncan be bought - pode ser comprada\r\ncan be sold\r\nminimum\r\nwastes-\r\n\r\nTeacher Laura 18:22\r\nthere are other wastes / it can be other wastes\r\nneeds to pass\r\nthrough\r\na test / a car test\r\nneeded to go to \r\nto fix/repair the car\r\nto pay\r\n\r\nTeacher Laura 18:27\r\naccomplish \r\nachievement \r\nfeeling- sensação\r\ndown on Earth\r\nmain - principal\r\nmain goals/ main street\r\n\r\nTeacher Laura 18:32\r\nwork for this\r\npractice/ develop/ improve\r\ndownloaded\r\nphysical job\r\njob historical\r\nstate house\r\n\r\nTeacher Laura 18:38\r\nbid - dar lance\r\nwhat helps\r\nguarantor\r\nwill be my\r\naunt /ents/\r\nchasing\r\nmy legs hurt\r\nmy legs still hurt but when I\'m stand up for long time\r\nbut that deep hurt\r\nintolerable /ɪn\'tɒlərəbəl/\r\n\r\nTeacher Laura 18:43\r\nno disposition/ no energy\r\n12\r\nin my house/ at home\r\n66\r\nheight\r\nmaximum \r\noveweight\r\nWeight scale\r\n\r\nTeacher Laura 18:54\r\nbrainwashing\r\ntour option', 'imobiliária = real estate (not state house> sorry)', 0, NULL, '2026-01-13 19:02:05', '2026-01-14 00:58:57'),
(5, 245, 46, '', 'to compete\r\nchange\r\nchange the prices\r\nto show/ to post\r\nto sell\r\n\r\nYou 14:15\r\nstinking / stain\r\n\r\ncheaper than BR\r\n\r\npassed\r\ndid in advance\r\ncar workshop\r\n\r\nturkey', 0, NULL, '2026-01-14 18:03:11', '2026-01-14 18:03:11'),
(6, 296, 31, '', 'passion fruit\r\nis facing\r\nthe back\r\nof the builing\r\npan/ pot\r\npans\r\nowner\r\nit\'s ahead- mais pra frente\r\n\r\nYou 8:47\r\nknew\r\nlet / released\r\n\r\nYou 8:54\r\nto spend the New Year\'s Eve\r\nbet', 0, NULL, '2026-01-15 12:03:01', '2026-01-15 12:03:01'),
(7, 258, 46, '', 'https://www.speaklanguages.com/english/phrases/more-common-expressions ', 0, NULL, '2026-01-15 17:40:42', '2026-01-15 17:40:42'),
(8, 210, 26, '', 'recovered\r\nwas\r\nit was supposed to\r\npro active\r\nconfidente\r\n\r\nBruna 15:17\r\nkhan academy kids\r\n\r\nYou 15:20\r\nsutitles\r\nsubtitles\r\nspelling\r\nI can understand / recognize\r\nit\'s fresh / it\'s in my mind\r\nproxy\r\n\r\nYou 15:53\r\nhe\'s shy\r\n\r\nYou 16:01\r\ntrust confiar', 0, NULL, '2026-01-15 19:09:37', '2026-01-15 19:09:37'),
(9, 190, 48, '', 'from: de > partida > ponto de origem \r\nto: para > direção > ir para algum lugar\r\nanswer key\r\n\r\nDiego Pilatti 8:45\r\nPerfect English Grammar\r\n\r\nYou 8:52\r\ngot/had/took great grade\r\n6/7 people\r\npeople are more interested\r\n\r\nYou 8:57\r\nsummary - resumo\r\n\r\nYou 9:07\r\nI\'ll not forget\r\n\r\nYou 9:15\r\nruined/ spoiled\r\nbuoy / bu:i/\r\n\r\nwas drowning - se afogando\r\nhoover\r\nhope', 0, NULL, '2026-01-16 12:34:15', '2026-01-16 12:34:15'),
(10, 352, 41, 'sweating\r\nplay\r\nhis\r\nhonestly\r\ntell you\r\nsleepyover party\r\nsunbath\r\n\r\nYou 9:47\r\nthere werent\r\ntext / speech\r\nfrom the bottom\r\nStart all over:\r\nMake a fresh start:\r\nthat kids like\r\n\r\nYou 9:52\r\nthere are\r\nthat I sang all my life\r\nthis series has\r\nfrom the 80s\r\nthe kids watch with their father\r\nspoke\r\nnice/friendly / outgoing\r\nsimpatic\r\nlikable\r\n\r\nYou 10:06\r\nscheduled day\r\nI got why you didnt\r\nI understood\r\n\r\nYou 10:11\r\nchill/ calm /easygoing\r\nbrother\'s girlfriend = sister-in-law\r\nin SP \r\nreturned to\r\nI gave to\r\nring\r\nfrom all the family\r\n\r\nYou 10:20\r\ncrowded - lotado', NULL, 0, NULL, '2026-01-16 13:43:13', '2026-01-16 13:43:13'),
(11, 171, 42, '', 'came back home to solve the problem\r\nfast / quickly\r\nspeed - velocidade\r\nproposes\r\nmuch news\r\n\r\nYou 13:13\r\nalone\r\nhe helped me to\r\nsave Money\r\nme with the Money\r\nmy boss will give me\r\ntours\r\nconfidente\r\nconfident\r\n\r\nYou 13:19\r\nencouraged\r\nMay 1st\r\n\r\nYou 13:30\r\nhow was = como foi\r\nI was drunk/ I drank a lot\r\ncaban\r\n\r\nYou 13:36\r\nnative speakers\r\nmany people from the world \r\nmany/a lot = muitas\r\ngold opportunity\r\n\r\nlack of - falta de\r\npriorities\r\nthe after never comes', 0, NULL, '2026-01-16 17:01:40', '2026-01-16 17:01:40'),
(12, 222, 26, '', 'dubbing\r\ncop\r\n\r\nYou 15:10\r\nshowed him\r\n\r\nYou 15:23\r\nphrases\r\npra mim você voltou\r\n\r\nYou 15:36\r\nouch\r\n\r\nYou 15:46\r\nat all- sequer/ de nenhuma maneira', 0, NULL, '2026-01-16 19:07:53', '2026-01-16 19:07:53'),
(13, 476, 29, '', 'school supplies\r\nitems\r\n\r\nYou 18:19\r\nas soon as you check\r\nas lower the price will be\r\n17.000\r\n12.000\r\ndivide/split\r\nshare\r\n5km lenght\r\n\r\nYou 18:32\r\nempty / less crowded\r\nneeded to go\r\nrating\r\n\r\nYou 18:38\r\nIf I were you\r\nNew Year\'s Eve\r\nthe summer\r\n\r\nYou 18:46\r\nthere isnt parking\r\n6 classes\r\n2 classes\r\n600 6 hundred', 0, NULL, '2026-01-19 21:55:40', '2026-01-19 21:55:40'),
(14, 319, 35, '', 'to make it up > pra compensar isso\r\nchill\r\nweed\r\ntry / tried\r\ndidnt work\r\nsmell\r\n\r\nYou 20:44\r\nprohibited\r\nlegal/ is free\r\nget back = volta \r\nput off = tirar roupa\r\nfelt a little pain in my legs\r\n\r\nYou 20:51\r\npatient\r\ntied\r\n\r\nLucas 21:01\r\n991451416\r\n\r\nPietra Seibt 21:03\r\n09087424957\r\n\r\nLucas 21:03\r\n82410200\r\n\r\nPietra Seibt 21:04\r\n665\r\n\r\nYou 21:10\r\nearn', 0, NULL, '2026-01-20 00:35:47', '2026-01-20 00:35:47'),
(15, 297, 31, '', 'yesterday, we welcomed one over main clients\r\nfor a onsite visit. It was they first time at our  office. I met them at the reception and offered coffee and water.\r\nThen, a gave them a short tour around the space  in introduce then to the team.\r\nAfter that, we had a meeting in the conference room to discuss are ongoing projects.\r\nThey asked a few questions and shared positive feedback on the last delivery.\r\nLater, we had lunch together and at restaurant nearby. \r\nOverall, the visit went very well.\r\nProfessional, friendly and productive.', 0, NULL, '2026-01-20 12:02:19', '2026-01-20 12:02:19'),
(16, 435, 52, 'his sister\r\nrude, strict, demand\r\nto solve\r\nfuel\r\npower/ energy\r\nit will be a diferente\r\ndifferent*\r\nquit\r\nresign\r\n\r\nYou 13:38\r\nwill not = wont > não irei\r\n2005\r\n\r\nYou 13:46\r\nShe\'s very shy to speak\r\n2x = twice\r\n\r\nYou 13:57\r\nall the people', NULL, 0, NULL, '2026-01-20 17:01:36', '2026-01-20 17:01:36'),
(17, 172, 42, '', 'did / do / will do', 0, NULL, '2026-01-20 18:01:11', '2026-01-20 18:01:11'),
(18, 198, 26, '', 'dare\r\naddicted to\r\nfilling\r\n\r\nYou 15:16\r\nI felt very bad\r\nknees - joelhos\r\nniece\r\n30\r\nwhat will be your plans?\r\nhow will you do?\r\n\r\nYou 15:22\r\nI miss eating \r\nI\'m missing eating\r\nher house was rented\r\nshe put her house to rente\r\n\r\nYou 15:29\r\nrised/ increaed\r\ngrew\r\nincreased\r\nread / red/\r\n\r\nYou 15:35\r\ni feel useless\r\nfault\r\nguilty\r\n\r\nYou 15:41\r\ntrial', 0, NULL, '2026-01-20 19:02:37', '2026-01-20 19:02:37'),
(19, 308, 30, 'onsite\r\nthere is a nice site here\r\n\r\nnearby\r\n\r\nAlice Guilhoto 8:52\r\nwe welcomed one over a main client\r\nfirst time an office\r\n\r\nYou 8:56\r\nmeet - met\r\n\r\nAlice Guilhoto 8:57\r\nat met them\r\ncoffe and water\r\nthen a gave\r\nwe had a meeting\r\n\r\nYou 8:59\r\nafter that\r\n\r\nAlice Guilhoto 8:59\r\nto discuss\r\nour going projects\r\n\r\nYou 9:00\r\nongoing - em andamento', NULL, 0, NULL, '2026-01-21 12:03:47', '2026-01-21 12:03:47'),
(20, 472, 29, '', 'I\'m still organizing \r\nyet> negative\r\nat the moment\r\ncame / got\r\nI\'ve just arrived\r\nof / from the gym\r\nas I said / as I told you\r\n\r\n\r\nAs far as I\'m concerned\r\nI would argue that…\r\nNot only that, but…\r\nLet’s not forget that…\r\nNevertheless,... > contudo\r\nEven so,... > mesmo assim\r\nI strongly believe that\r\nWithout a doubt,…\r\n\r\nYou 18:29\r\nhow many stars and planets > Always plural \r\nother lives // lifes\r\nET\r\npyramids\r\nwas built - foi construída\r\ntools - ferramentas\r\nstones\r\nabove each other\r\nrestricted zone\r\n\r\nrobots\r\nthemselves\r\nall the night / all night long\r\n\r\non Earth\r\nWhat I mean\r\nbia/ biased \r\nI was 20 years old\r\npeople were happier\r\nchildhood\r\n\r\nI\'d try to live\r\ntheme /topic\r\nbia/ biased', 0, NULL, '2026-01-21 21:56:36', '2026-01-21 21:56:36'),
(21, 312, 18, 'What are you going to call your invention?\r\nR: NailBot\r\n\r\nHow is it going to work?\r\nR: It is going to use sensors to detect the size and shape of the nails. You just need to place your hand inside the device, and a small, safe rotary blade is going to cut your nails automatically in seconds.\r\n\r\nWhat problem is it going to solve?\r\nR:It is going to solve two main problems: it saves time for busy people and helps people who dosent like using manual clippers.\r\n\r\nHow much is it going to cost?\r\nR: It is going to cost about R$120,00\r\n\r\nWho is going to buy it?\r\nR: Busy professionals, parents who struggle to cut their children\'s nails, and elderly people are going to buy it.', NULL, 0, NULL, '2026-01-22 17:29:15', '2026-01-22 17:29:15'),
(22, 211, 26, '', 'https://test-english.com/grammar-points/a1/past-simple-negatives-questions/ homework', 0, NULL, '2026-01-22 19:01:08', '2026-01-22 19:02:08'),
(23, 353, 41, '', 'It\'s Queen\'s song which it\'s called\r\nwhole\r\n\r\nTathiane S 9:50\r\nwhole\r\n\r\nYou 9:50\r\n/rol/\r\n\r\nTathiane S 9:51\r\nlaughed\r\n\r\nYou 9:51\r\nlaughed /léft/\r\nlaugh\r\nsafe\r\nforecast\r\nit seems\r\nI learned\r\n\r\nTathiane S 9:55\r\nstill\r\n\r\nYou 9:55\r\nyet > negative\r\nare you still here?\r\nhave you finished the house yet?\r\ncleaning\r\nsong / music\r\nmusics > nowadays musics\r\nsongs\r\ntracks\r\n\r\nYou 10:01\r\nget down to business\r\n20 07 \r\n20 oh seven \r\n2 Thousand 7\r\nmainly - princiaplmente\r\nfar away places\r\nwe took', 0, NULL, '2026-01-23 13:44:05', '2026-01-23 13:44:05'),
(24, 459, 52, '', 'cable car\r\nthe waiting is\r\ncrowded\r\namusement park\r\nrides\r\nshe\'s afraid\r\nit will be\r\n\r\nYou 13:39\r\nLet\'s get down to business\r\n\r\nYou 13:59\r\nwho whom', 0, NULL, '2026-01-23 17:04:28', '2026-01-23 17:04:28'),
(25, 235, 46, '', 'board games\r\nbuild - construir\r\nI won\r\n\r\nYou 14:13\r\ndeck of cards\r\nluck or reversal\r\n\r\nYou 14:25\r\nwith one card\r\nfor each player\r\nforehead\r\nsecond round\r\nit\'s go on\r\nwhat you said\r\nyou lose/ get lost\r\nscrews up\r\nget screwed\r\nscroll\r\ntaught her - ensinei\r\ntaught > teach\r\n\r\nYou 14:31\r\nget sick\r\nget full - se enche\r\nachieve\r\nget\r\n\r\nYou 14:42\r\nshe doesnt work\r\ndaily routine', 0, NULL, '2026-01-26 18:06:42', '2026-01-26 18:06:42'),
(26, 320, 35, '', 'On Friday pedro and I went to the beach, on saturday in the morning pedro and I saw the sunrise after i ran and went to thhe beach why my mom and my mother in law and my father and pedro saw the guaratuba bridge the build and in the afternoon i ate a ice cream and on sunday we returned to curitiba and we got a long traffic but ok, and you teacher what did you do in your weekend?\r\n\r\nLucas 20:39\r\nwell, This was my last weekend in vacation at school, and I enjoy to rest a lot and play and watch a lot too. On sunday I went to play soccer , but the field was terrible because this I felt a litlle pain in my anckles and my knees, for my this is so sad because i like so much the team but I think i will can play more in this field\r\n\r\nYou 20:42\r\nknown\r\nit\'s known in\r\neach scoop\r\nholes\r\n\r\nYou 20:49\r\nsummarize\r\nspeech/speak/talk\r\n\r\nLucas 20:49\r\nThis weekend your boyfriend birthday 30 years, and there is a festival in your city, you enjoeyd a very delicious barbecue, and the weekend was very fun\r\n\r\nPietra 20:50\r\nwas my lucas birthday an dnow its 30 lucas family came to jundiai arrived friday night there is a festival here and saturday very nice and we made a barbecue it was pretty cool and lucas and cousin play for many times 5 or 6 hours play too much and on sunday family comeback to curitiba and clean the house and in the afternoon we went to festival again\r\n\r\nYou 20:53\r\nthere were 2\r\ntalks\r\nlectures\r\nI hope\r\ntimetable\r\n\r\nYou 21:03\r\ntwo weeks delayed\r\nI could get\r\n\r\nYou 21:13\r\nfell / hit\r\ndiscuss/argue/fight\r\n\r\nYou 21:18\r\nmain - principal\r\n\r\nYou 21:24\r\ngive in / give way\r\nsubject\r\nbury - enterrar\r\nI would be judged by\r\n\r\nYou 21:31\r\ngot on tie\r\nscored the winner goal', 0, NULL, '2026-01-27 00:33:14', '2026-01-27 00:33:14'),
(27, 289, 31, '', 'Hi \r\nI\'m having a trouble\r\nacessing the CIM plataform\r\nEvery time I try to log in\r\nI get in air message saying:\r\naccess denied.Invalid credentials\r\nI\'ve already tried resetting my password\r\nbut the issue persists.\r\nI\'m using google chrome on the windows\r\n11 laptop.\r\nThis started happening this morning\r\naround 9:00am \r\nCould you please help me check watch \r\nmight be wrong?\r\nThank you very much.', 0, NULL, '2026-01-27 12:01:12', '2026-01-27 12:01:12'),
(28, 427, 52, '', 'they do a party\r\nwent to a rest. to have lunch > ate / had lunch\r\nthere are rare cars\r\nreleased\r\nwas killed\r\nferris wheel\r\nrode - já andei\r\nride\r\n\r\nYou 13:39\r\nfreezing\r\ntranquil, easy, chill, easygoing\r\nthe view\r\nI\'m used to it\r\nnicer / more adventurous \r\ncrowd > multidão // crowded > cheio de gente\r\n\r\nYou 13:45\r\ngo on tours\r\ndays are more\r\nI will not be here\r\n\r\nYou 13:51\r\ndidnt pause\r\nbusier - mais agitado\r\nsuppliers\r\nworkers\r\nprofessionals\r\nbudget - orçamento\r\nconcreting the slab\r\n\r\nYou 13:56\r\ninterrupt / disturb/ bother\r\nworktime\r\nI\'ve already solved\r\nmove on', 0, NULL, '2026-01-27 17:00:33', '2026-01-27 17:00:33'),
(29, 159, 34, '', 'I\'m sick\r\nnot too much\r\ntoo much\r\na lot of work\r\nrecovering\r\ndidnt have\r\n\r\nYou 20:27\r\nsome things\r\n\r\nYou 20:38\r\nI\'m not in the mood\r\n\r\nYou 20:49\r\nnearby - por perto\r\nbuy - bye- by\r\nwelcomed\r\nmain client \r\nmain - principal\r\nonsite\r\nI met them\r\nmeet > met\r\n\r\nYou 20:54\r\nshort tour\r\nintroduce ... to\r\ndiscuss\r\nongoing - em andamento\r\n\r\nYou 20:59\r\nasked\r\nask/t/\r\nshared\r\npositive\r\non the last delivery', 0, NULL, '2026-01-28 00:03:14', '2026-01-28 00:03:14'),
(30, 444, 52, '', 'spent her birthday\r\nsend flowers to her\r\nwill be\r\ncalmed me\r\nmy leaving\r\nsince July\r\nneeds to be judged\r\n\r\nYou 13:39\r\nI didnt apply for it\r\nwithout the need of going to\r\npossible = possível \r\npossibility = possibilidade\r\nwe\'re late in 60 days\r\nin the previewed cost\r\n\r\nYou 13:45\r\nI have already followed\r\nI\'ve followed\r\neach brick\r\ndont lose - não perde\r\ncertain / sure\r\ngrow/rise/inscrease\r\nproperties\r\nsimilarities\r\ngoal / aim / target / objective\r\nbreak this - quebrar isso \r\nbroke this - quebrou isso\r\nachieve / conquer / realize\r\n\r\nYou 13:54\r\nclay - barro\r\nsubfloor\r\nslab - laje\r\nbefore traveling/ coming here\r\nmainly - principalmente\r\nearthwork\r\n\r\nYou 13:59\r\ndrilling - perfuração\r\nloss', 0, NULL, '2026-01-28 17:01:21', '2026-01-28 17:01:21'),
(31, 247, 46, '', 'https://test-english.com/grammar-points/a1/past-simple-regular-irregular/2/ > homework', 0, NULL, '2026-01-28 18:16:52', '2026-01-28 18:16:52'),
(32, 342, 35, '', 'lecture / talks\r\nwere kidding/joking\r\nseem\r\nseemed\r\nwhile I was there\r\nuntil I was there\r\nunderstood\r\nhow the education helps\r\nmean\r\n\r\nLucas 20:39\r\nmeanig\r\n\r\nYou 20:39\r\nmeaning\r\n\r\nYou 20:42\r\ndizzle\r\n\r\nYou 20:42\r\ndrizzle\r\n\r\nYou 20:42\r\nlight rain\r\nlighting\r\nseaside\r\nNews\r\na News\r\nhips\r\nwith it\r\n\r\nYou 20:47\r\nafter the running\r\nstretch\r\nget down to business\r\n\r\nYou 21:03\r\nchoice - escolha\r\nchoose / chose / chosen\r\n\r\nYou 21:15\r\nassign tasks: atribuir\r\n\r\nLucas 21:22\r\nLet´s stop the talks and let´s get the ball rolling\r\n\r\nPietra 21:22\r\ntoday my boss tell me the get the ball rolling on the next experiment\r\ntold me*\r\n\r\nLucas 21:24\r\nOk we now he did a mistake and the pass but let´s give soemone the benefit of the doubt this time\r\n\r\nPietra 21:24\r\nthe doctor said about the inflammation its easy and i give someone the benefit of the doubt\r\nnow I need to go the extra mile if I want to pass the next public exam\r\n\r\nLucas 21:26\r\nWe need go  the extra mile for allthings finish today.\r\n\r\nYou 21:26\r\nneed to\r\n\r\nPietra 21:27\r\nwhen I explain a concept for my students i try to hit the nail on the head in the class\r\n\r\nYou 21:27\r\nbe done / be finished\r\n\r\nLucas 21:27\r\nWe need to go the extra mile for be finished today\r\n\r\nPietra 21:28\r\ni always cry in the heat of the moment in the discussion\r\nduring the class\r\nwhen I explain a concept for my students i try to hit the nail on the head during the class\r\ni always cry in the heat of the moment during the discussion\r\n\r\nLucas 21:30\r\nMy children hit the nail on the head the name about the exercise\r\n\r\nPietra 21:30\r\nwe turned friends in our first class it was a piece of cake for me\r\n\r\nYou 21:31\r\nbecome - became\r\n\r\nLucas 21:31\r\nSorry for that in the last week I did in the heat of the moment\r\n\r\nPietra 21:31\r\nwe became friends in our first class it was a piece of cake for me', 0, NULL, '2026-01-29 00:33:11', '2026-01-29 00:33:11'),
(33, 298, 31, '', 'Subject: VPN access - access denied\r\n\r\nDear Support IT team,\r\n\r\nI\'m having a trouble with VPN access because\r\nit is saying \"Access denied\". I used the VPN normally\r\nyesterday.\r\n\r\nI use the VPN to access the client environment. \r\n\r\nI attached the screenshot with the error message.\r\n\r\nCould you might help me check my credencials?\r\n\r\nThank you,\r\nFabiane', 0, NULL, '2026-01-29 12:02:20', '2026-01-29 12:02:20'),
(34, 260, 46, '', 'Nicky> homework\r\nWrite about your weekend: put everything in the past', 0, NULL, '2026-01-29 18:03:28', '2026-01-29 18:03:28'),
(35, 212, 26, '', 'I\'m bored to stay\r\nthere was a tornado/ wind\r\nblowing\r\nunder the weather - meio doente\r\n12\r\n\r\nYou 15:13\r\nshe\'s pregnant \r\npregnancy\r\nbelly\r\ngrowing\r\nstuffed\r\nstew - estufada\r\nwill be the Godparents\r\nmad - louca \r\nson / daughter / kids=children\r\nas long as she\r\n\r\nYou 15:19\r\nbig child / she\'s 9\r\nspoiling\r\nis better to have a baby\r\ncheaper\r\n\r\nYou 15:27\r\nGoddaughter\r\n\r\nYou 15:36\r\nher husband\r\nhas\r\n3 children\r\nshe wanna get pregnant\r\n\r\nYou 15:53\r\ntheater\r\ntheatre\r\n\r\nYou 15:58\r\nthose boots there too', 0, NULL, '2026-01-29 19:04:35', '2026-01-29 19:04:35'),
(36, 460, 52, '', 'I\'m not sure\r\nwas\r\nwas 8 months ago\r\nthe tours are crowded\r\nhear me?\r\n\r\nthiago.zattoni 13:38\r\nyes\r\n\r\nYou 13:40\r\norder/sequence\r\ncheck the weather\r\nforecast\r\nhotter\r\nthese days are\r\nless crowded\r\nfrom Wed\r\non Mon, on Wed\r\nI already went\r\nthere are left\r\nleft those tours\r\nnear BC\r\nmaybe\r\n\r\nYou 13:47\r\nwith hurry\r\nconcrete\r\ninner pool\r\ninside the house\r\nisnt heated or covered\r\nwe didnt have\r\nsolve\r\nplumber\r\n\r\nYou 13:52\r\ncontracted/ hired\r\nthat I drew\r\nincompetent, inept, unqualified\r\ndidnt do the correct draw\r\nin a certain way\r\nassumed\r\nlate at night\r\nit passes/ passed\r\nin her land\r\nhavent used \r\ndidnt use\r\nat least\r\nspace/place\r\nroom\r\nturn on\r\n\r\nYou 13:59\r\nbackyard / garden\r\nwould lose\r\nI\'ll lose inside \r\nI lost inside > past\r\nduties', 0, NULL, '2026-01-30 17:04:07', '2026-01-30 17:04:07'),
(37, 224, 26, '', 'https://test-english.com/vocabulary/a2/words-with-prepositions-a2-english-vocabulary/ homework', 0, NULL, '2026-01-30 19:03:46', '2026-01-30 19:03:46'),
(38, 518, 54, 'Duble = duas vezes ex: 99 > duble nine \r\nbirthday = berf day \r\nLast name = Ultimo sobrenome\r\nI will be= futuro  ex: I wil be 19 march \r\nI was = passado ex: I was 2 thausand 7 \r\n Thausand= mil \r\nhusdred= cem \r\nhobby= ing ex: reanding books \r\n', NULL, 0, NULL, '2026-01-31 14:17:14', '2026-01-31 14:17:14'),
(39, 290, 31, '', 'went off\r\nInflatable toy: Termo geral para qualquer brinquedo inflável.\r\nBouncy castle: O clássico pula-pula inflável.\r\nBounce house / Inflatable bouncer: Termos comuns para estruturas infláveis de pular.\r\nInflatable slide: Escorregador inflável.\r\n\r\nYou 9:22\r\n2 packs\r\ntea spoon\r\n\r\nYou 9:48\r\nIf you ask me…\r\nIt seems to me that…\r\nNot only that, but...\r\nWhat’s more,...\r\n\r\nYou 9:54\r\nbeings - seres \r\nbe-ser\r\ndemons', 0, NULL, '2026-02-02 13:00:58', '2026-02-02 13:00:58'),
(40, 469, 29, '', 'will pass fast\r\nforest / woods\r\nwe were\r\nwe could climb\r\nwe were able to climb\r\neveryone have climbed\r\nthe person stays\r\ngets very tired\r\n\r\nYou 18:13\r\nuphill - subida\r\nget at the top\r\nview/ landscape\r\nwalks more slowly\r\n\r\nYou 18:19\r\nwe were able \r\nexpand / spend\r\nJog / Jogging:\r\nhe\'s younger\r\npeople that were born\r\nin\r\nthe life knowledge\r\npassed through\r\nsigns\r\n\r\nYou 18:25\r\nspecialist\r\nLeo\r\nPiscis\r\npisces*\r\nshe prefered\r\nshe will prefer a trip\r\nmake a trip = travel \r\ntrip = viagem\r\ntravel = viajar\r\nchose/ prefered\r\n\r\nYou 18:34\r\nget this day off\r\nbirthday - nascimento\r\nanniversary\r\nwe were able \r\nexpand / spend\r\ndeadline : prazo\r\n\r\nYou 18:51\r\nput together / join\r\nthese two things\r\ni was able/ we were able \r\nexpand / spend\r\nexpanded\r\nspent more time\r\n\r\nYou 18:58\r\nwill wear more relaxed/informal/casual clothes', 0, NULL, '2026-02-02 22:02:17', '2026-02-02 22:02:17'),
(41, 274, 28, '', 'long time friend\r\ncomplain\r\nbeginning of the year\r\n45 of the second half\r\nget down to business\r\n\r\nYou 19:40\r\ncharacter\r\ntook pill for my\r\ntake/took\r\n\r\nMurilo Marins - MOTIVA 20:00\r\nGiovanna Malerba Del Passo Silva\r\n\r\nYou 20:01\r\nput off/ took off\r\ntook to\r\nregristy office\r\ntook the paper to registry\r\n\r\nYou 20:31\r\nsleep\r\ntake a nap', 0, NULL, '2026-02-02 23:32:41', '2026-02-02 23:32:41'),
(42, 321, 36, '', 'Lucas, check those pronunciation \r\nofficials - əˈfɪʃəlz \r\nconfusion - kənˈfjuʒən\r\nrejected - rɪˈʤɛktɪd \r\nresist - rɪˈzɪst\r\ncheck - ʧɛk  ', 0, NULL, '2026-02-03 00:32:36', '2026-02-04 18:41:12'),
(43, 278, 43, '', 'odos os dias, fazendeiros precisam aplicar diferentes técnicas no campo.\r\nEles adicionam fertilizantes no solo e consertam maquinas quando não estão funcionando bem.\r\ndurante a época de plantio, eles semeiam as sementes e depois cultivam as culturas com cuidado.\r\n\r\nLeandro Hipolito 18:32\r\nquando as plantas crescem demais, eles aparam e removem as partes danificadas.\r\nantes da colheita, produtores geralmente tratam as plantas e protegem contra as doenças.\r\nem alguns casos, eles aplicam calcário no solo para aumentar a fertilidade do solo.\r\ndepois disso, eles rotulam os containers e repetem o processo em outras áreas.\r\n\r\nLeandro Hipolito 18:37\r\nEssas ações fazem parte da gestão diária da fazeda e ajudam aumentar a produtividade e qualidade das culturas.', 0, NULL, '2026-02-03 21:58:01', '2026-02-03 21:58:01'),
(44, 278, 44, '', 'na fazenda tem muitas culturas diferentes crescem juntas.\r\nsoja,milho e trigo são muitos comuns em grandes campos my friend\r\nfazendeiros também plantam vegetais  como: alface, espinafre,tomate e pepino.\r\n\r\nuser 18:46\r\nem menores áreas,  eles cultivam cenoura,cebola, alho e quiabo.\r\nalgumas fazendas produzem aboboras e mandiocas e leva no mercado local(feirinha)\r\ncada  planta precisa  de solo corrigido, agua e cuidado.\r\n\r\nuser 18:53\r\ndurante essa etapa, fazendeiros checam as plantas todo dia para ver se estão saudáveis.\r\nessas culturas são importante para alimentação e para a rconomia local.\r\nbons manejos ajudam os fazendeiros para obter melhores resultados  e plantas fortes.', 0, NULL, '2026-02-03 21:58:14', '2026-02-03 21:58:14'),
(45, 156, 33, '', 'sand\r\nallow\r\nallowed\r\nallow/ deny\r\nget down to business\r\n\r\nYou 20:10\r\nwindow shopping\r\n\r\nYou 20:16\r\nprocess yield\r\n\r\nMari 20:48\r\nTrouble\r\n\r\nMariele Fernandes 20:48\r\nTrouble\r\n\r\nMari 20:49\r\nAcsessing\r\n\r\nMariele Fernandes 20:49\r\nacsessing\r\n\r\nYou 20:49\r\naccessing\r\n\r\nMariele Fernandes 20:49\r\nalmost\r\naccess\r\nSRM\r\n\r\nYou 20:50\r\nCRM\r\nlog in\r\nlogin\r\n\r\nMariele Fernandes 20:54\r\nSeeing\r\n\r\nMari 20:54\r\nAir mensagem saying\r\n\r\nYou 20:54\r\nsaying\r\n\r\nMari 20:55\r\nAccess denyed\r\n\r\nYou 20:55\r\naccess denied\r\n\r\nMariele Fernandes 20:55\r\ninvalid credencials\r\n\r\nYou 20:56\r\nI\'ve already tried\r\n\r\nMariele Fernandes 20:57\r\nreseting\r\n\r\nYou 20:57\r\nresetting\r\n\r\nMariele Fernandes 20:57\r\npassword\r\n\r\nMari 20:58\r\nPassword\r\n\r\nYou 20:58\r\nbut the issue\r\npersists\r\non\r\nstarted happening\r\n\r\nMariele Fernandes 21:00\r\nhappening\r\n\r\nYou 21:02\r\nhelp me\r\nmight\r\nbe wrong\r\nwhat might be wrong\r\nmight - pode/possivelmente', 0, NULL, '2026-02-04 00:04:34', '2026-02-04 00:04:34'),
(46, 184, 39, 'House = casa  lugar\r\nHome lar\r\n\r\n\r\nso so \r\nmore or less', NULL, 0, NULL, '2026-02-04 14:49:27', '2026-02-04 14:49:27'),
(47, 321, 35, '', 'Pi, check the pronunciation\r\nordered - confirmed \r\nreported - rejected \r\nˈɔrdərd - kənˈfɜrmd\r\nˌriˈpɔrtəd - rɪˈʤɛktɪd', 0, NULL, '2026-02-04 18:54:02', '2026-02-04 18:54:02'),
(48, 299, 31, '', 'complained\r\nsimulatrs\r\nis better than\r\n\r\nYou 8:39\r\nIf you ask me…\r\nIt seems to me that \r\nAs far as I\'m concerned\r\nLet’s not forget that\r\nIt’s also worth mentioning that\r\nrained\r\n\r\nYou 8:44\r\nthose hours/ that time\r\nDespite that\r\nEven so,\r\nWithout a doubt,… \r\nIt’s undeniable that', 0, NULL, '2026-02-05 12:02:22', '2026-02-05 12:02:22'),
(49, 199, 26, '', 'excuse\r\nto see\r\nto talk\r\nbroke up\r\nconfidence\r\nI know him for 3 years\r\nimproved / rose\r\nhe has\r\ngot better\r\nfor / since \r\nfor x years\r\nsince 2023\r\nfor almost\r\n\r\nYou 11:39\r\nspeak\r\nlaugh\r\n\r\nYou 12:15\r\nage group\r\nage range', 0, NULL, '2026-02-05 15:31:14', '2026-02-05 15:31:14'),
(50, 213, 26, '', 'uncharted\r\n\r\nYou 15:08\r\nas you choose\r\nas you make your choices\r\ndepending\r\nsafe - seguro\r\nsave\r\nsorry to interrupt\r\n\r\nYou 15:59\r\ntag\r\n\r\nBruna 16:01\r\ntadhg', 0, NULL, '2026-02-05 19:03:54', '2026-02-05 19:03:54'),
(51, 480, 45, '', 'I delayed\r\nfired\r\ntuesday\r\nthrusday\r\nwe would be fired\r\nthe owner of the company\r\nhe sent me a message and he told \r\nhe would talk to my coworker\r\n\r\nYou 18:08\r\nfarewell\r\nbefore firing him\r\nneeded to pretend\r\nthat I didnt know\r\nso far- até então\r\nlooking for/ searching\r\nthere arent people for the vacancy\r\nthey didnt choose\r\n\r\nYou 18:14\r\nneeded\r\nto be able to work\r\nfor them\r\nhistorical\r\nit wasnt\r\nfair\r\n\r\nYou 18:19\r\nhe likes to cut corners \r\n\r\nanxious\r\ncriticized\r\nburocratic\r\ntelling/ saying\r\nboring\r\nI feel bored using it\r\n\r\nYou 18:24\r\nupdated\r\nI\'m used to use\r\nperfectionist\r\nI get annoyed\r\ncharged\r\n\r\nYou 18:29\r\nnext episodes\r\nhe needs to do\r\nreliable person/worker\r\nHR\r\nhuman resources\r\n\r\nYou 18:39\r\nfield / area\r\nbranche\r\narchaic\r\noutdated\r\nexceeded\r\ncourse\r\n\r\nYou 18:46\r\nSystems Analysis and Development\r\nas mine\r\ndegree certification\r\n\r\nYou 18:52\r\ndelivery/ hand in: entregar\r\nit doesnt/ didnt work\r\nlazy\r\ntip/ suggestion\r\nmajor course\r\nstrategy\r\n\r\nprove\r\nit\'s used\r\nit\'s Worth it', 0, NULL, '2026-02-05 22:01:54', '2026-02-05 22:01:54'),
(52, 343, 35, 'My words: ordered, confirmed, reported, rejected\r\nWell, this week I studied and worked a lot, because of and my qualification. Then, my boss ordered me to do the datas analysis of my experiments. So, yesterday I did this task and confirmed my hypothesis about chronic kidney disease about I reported on my preview research. Now I send to my boss and I hope to not rejected my datas and my ideas on the discussion about this topic.', 'Great job, Pi! ', 1, '2026-02-14 18:02:48', '2026-02-06 17:30:19', '2026-02-14 18:02:48'),
(53, 519, 54, 'Homework:\r\n1)	I wake up at 9:00\r\n2)	I have coffee at 10:00 a.m.\r\n3)	I take a shower at 11:00 a.m.\r\n4)	I brush my teeth at 11:00 a.m.\r\n5)	I go to work at 6:00 a.m.\r\n6)	I have dinner at 8:00 p.m.\r\n7)	I study at 8:00 a.m.\r\n8)	I have lunch at 12 p.m.\r\n9)	I go to sleep at 11 p.m.\r\n10)	I get up at 6:00 a.m.\r\n\r\n', NULL, 0, NULL, '2026-02-07 13:23:13', '2026-02-07 13:23:13'),
(54, 201, 26, '', 'it gives much work\r\nI got proud of\r\nshe told me\r\nshe said\r\nthat I\'ll change\r\nNicky\'s docs\r\n\r\nYou 15:09\r\nto my own house\r\n1600\r\nhe\'s out of his mind\r\nhe\'s insane\r\npack\r\npacking\r\nboxing\r\nmore expensive\r\na bit more\r\napplied\r\n\r\nYou 15:14\r\ngets right\r\nrock the boats\r\nappliences\r\nchest of drawers\r\nthere are somethings\r\nwardrobe\r\n\r\nYou 15:22\r\nwe\'re not scared/nervous\r\ni dont want\r\nin last case\r\nis the deadline\r\nghosted -desapareceu\r\n\r\nYou 15:28\r\nshopaholic\r\ndish washer\r\ndish/dishes - louça\r\nfrom tap\r\ntapped water\r\nhe gave\r\n\r\nYou 15:46\r\nfancy\r\nelegant\r\ngrapes juice\r\n\r\nYou 16:00\r\nthe house has\r\nthere is\r\nmattress', 0, NULL, '2026-02-10 19:01:03', '2026-02-10 19:01:03'),
(55, 185, 39, '', 'in the weekend\r\nrested\r\nLet me pick/grab/take a glass of water\r\nmother\'s house\r\ncame\r\nmaster\'s thesis committee\r\nmaster\'s thesis defense\r\nwe\'re all in the same boat\r\n\r\nEliana Chubaci 11:15\r\nTiny- minusculo\r\n\r\nYou 11:29\r\ndelivery\r\n\r\nYou 11:35\r\nadministrators\r\nbusinesspeople\r\nentrepreneurs', 0, NULL, '2026-02-11 15:02:56', '2026-02-11 15:02:56'),
(56, 185, 38, '', 'in the weekend\r\nrested\r\nLet me pick/grab/take a glass of water\r\nmother\'s house\r\ncame\r\nmaster\'s thesis committee\r\nmaster\'s thesis defense\r\nwe\'re all in the same boat\r\n\r\nEliana Chubaci 11:15\r\nTiny- minusculo\r\n\r\nYou 11:29\r\ndelivery\r\n\r\nYou 11:35\r\nadministrators\r\nbusinesspeople\r\nentrepreneurs', 0, NULL, '2026-02-11 15:03:05', '2026-02-11 15:03:05'),
(57, 446, 52, '', 'I had\r\ncried\r\nget used\r\nshy\r\nthat - que\r\naway from\r\n\r\nYou 13:38\r\nwho cried more\r\nduring/in the break\r\ncomfort\r\nthe nearest\r\n\r\nYou 13:44\r\nrested\r\nfor this reason\r\ntaht\'s why\r\nbecause of it\r\nI liked\r\nhottest day in the week\r\nget a shadow\r\nyou got cold\r\naltitude\r\nslab\r\n\r\nYou 13:49\r\nseemed\r\nbig/bigger / large\r\ngarage/ machine house/\r\nperception\r\ntook 3 years\r\nto be finished - pra ser terminado\r\ntook my family there / to know there\r\ndiscussed\r\nmade the best\r\nis getting\r\nis becoming\r\nshe\'s calling, I need to answer\r\n\r\nYou 13:54\r\nare seeing\r\nall Worth it\r\nworth - valer\r\nowe - dever\r\nwould be - seria \r\nwill be - será\r\nmiddle-size', 0, NULL, '2026-02-11 16:58:41', '2026-02-11 16:58:41'),
(58, 214, 26, '', 'I was waiting\r\nalmost fell asleep\r\nfall asleep\r\nwe got the house\r\nin the bags\r\ndaily clothes\r\ntalked to Nicky - conversei com ele\r\n\r\nYou 15:12\r\nhis mother\r\nwardrobe\r\nwhen it got it\r\nreal estate\r\nestate agency\r\ncalled me to say\r\nin those sit\r\nthat we see\r\nthat we\'re\r\ngrowing\r\nimproving\r\npersonal growth\r\n\r\nYou 15:17\r\nI\'m not stressed\r\nhit the sack\r\nsleep heavy\r\nI would be happier \r\nif it was my own house\r\ndont give problems\r\nowner\r\nextend the contract\r\n\r\nYou 15:23\r\n4 streets away\r\nI didnt take/get\r\nit was raining\r\n\r\nYou 15:31\r\nit succeeded', 0, NULL, '2026-02-12 19:03:13', '2026-02-12 19:03:13'),
(59, 481, 45, '', 'migraine\r\nsome times\r\nI get/am sick too\r\nget/catch\r\nexit\r\n\r\nYou 18:07\r\nschedule/ plan \r\nI get\r\nafraid\r\nhe saw a news\r\nwastewater system\r\nis throw\r\nin the water of the sea\r\nin the sea water\r\n\r\nYou 18:14\r\nfarther\r\ntake sunbath\r\nsunscreen / sunblock\r\nskin - pele\r\nlow season\r\n\r\nYou 18:20\r\ntwenty-third/three\r\nspend ... days\r\nreally enjoy\r\ninappropriate water\r\ntouching, hitting\r\njellyfish - água viva\r\n\r\nYou 18:26\r\nI have fear \r\nI\'m afraid\r\nof\r\nthere is a lot of water\r\nneeds\r\ndo their needs\r\nmuch worse\r\n\r\nYou 18:31\r\nget into\r\ncousin\r\nher wife\r\ncouple\r\n\r\nYou 18:41\r\nshow off \r\ncompetences\r\nwants to charge more\r\nwants to demand more\r\nconflict\r\namong them\r\nbetween them\r\n\r\nYou 18:50\r\naffected\r\nhe\'s suing\r\nmad\r\nlabor market\r\ncompetitors\r\n\r\nYou 18:57\r\nidentified myself', 0, NULL, '2026-02-12 22:01:25', '2026-02-12 22:01:25'),
(60, 462, 52, '', 'didnt take a toy\r\ndidnt want to take\r\nintroduced/ integrated\r\nlet\r\ngot off\r\nreplaced by\r\n\r\nYou 13:41\r\ngetting in order\r\nwill get in order\r\nthings are getting order\r\nmake it up\r\nagree/ deal\r\nin advance\r\n\r\nYou 13:46\r\nschedule\r\nI had\r\nit was ok \r\nI had excuse to speed up\r\nlunchbreak / lunchtime\r\nI didnt have\r\ndrink a glass/cup of milk\r\n\r\nYou 13:52\r\nI police more\r\n\'big deal\'\r\nI went to a very good restaurant\r\nfine dining / high gastronomy\r\ncost-benefit\r\nfor her\r\nI think/ I guess/ I Suppose\r\nlobster - lagosta \r\nsea food\r\n\r\nYou 13:58\r\nonce per year - 1x por ano\r\n1x once \r\n2x twice', 0, NULL, '2026-02-13 17:00:23', '2026-02-13 17:00:23'),
(61, 529, 56, '', 'Manufacturing Engineering // Production Engineering\r\n\r\nnice Holiday', 0, NULL, '2026-02-13 22:04:44', '2026-02-13 22:04:44'),
(62, 522, 57, 'put colocar\r\nput on - vestir/por\r\nput here\r\nput there please\r\nput on your shoes\r\ntake off\r\ntake off - tirar\r\nsee - ver / saw - viu\r\n\r\nlook here/ there\r\nstretch - esticar/ alongar ', NULL, 0, NULL, '2026-02-13 23:32:05', '2026-02-13 23:32:05'),
(63, 541, 57, '', 'like that ', 0, NULL, '2026-02-14 14:07:37', '2026-02-14 14:46:20');

-- --------------------------------------------------------

--
-- Estrutura para tabela `anotacoes_itens`
--

CREATE TABLE `anotacoes_itens` (
  `id` int(11) NOT NULL,
  `anotacao_id` int(11) NOT NULL,
  `autor` varchar(50) NOT NULL,
  `conteudo` text NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `anotacoes_itens`
--

INSERT INTO `anotacoes_itens` (`id`, `anotacao_id`, `autor`, `conteudo`, `data_criacao`) VALUES
(1, 1, 'aluno', 'teste', '2025-12-19 10:23:58'),
(2, 3, 'aluno', 'fireworks\r\non the sidewalk\r\ncurb\r\nshort way\r\nshe crashed\r\nthirty-one / thirty-first\r\nmy aunt /ent/\r\n\r\n\r\n1st first\r\ntwenty-first\r\ntwenty-second\r\nthird\r\nfourth - tenth\r\nninth\r\n29 twenty-ninth\r\nfourth\r\nsixth\r\n2025\r\nnew / released\r\nfor who\r\nBruna asked\r\nall channels\r\nHistory channel\r\n\r\nsecond last\r\nreferee - juiz\r\nrefe RÍ\r\neach time - cada tempo\r\nbreaks', '2026-01-13 17:57:15'),
(3, 4, 'aluno', 'lose Weight\r\non my cellphone\r\nend of the year\r\nHolidays\r\nreceiving visits\r\n\r\nTeacher Laura 18:11\r\nuseless\r\ndid you go to the beach?\r\ncheap\r\n\r\nTeacher Laura 18:17\r\nsea /si/ = see\r\nsave Money\r\nfor the entrance\r\ncozy\r\ncan be bought - pode ser comprada\r\ncan be sold\r\nminimum\r\nwastes-\r\n\r\nTeacher Laura 18:22\r\nthere are other wastes / it can be other wastes\r\nneeds to pass\r\nthrough\r\na test / a car test\r\nneeded to go to \r\nto fix/repair the car\r\nto pay\r\n\r\nTeacher Laura 18:27\r\naccomplish \r\nachievement \r\nfeeling- sensação\r\ndown on Earth\r\nmain - principal\r\nmain goals/ main street\r\n\r\nTeacher Laura 18:32\r\nwork for this\r\npractice/ develop/ improve\r\ndownloaded\r\nphysical job\r\njob historical\r\nstate house\r\n\r\nTeacher Laura 18:38\r\nbid - dar lance\r\nwhat helps\r\nguarantor\r\nwill be my\r\naunt /ents/\r\nchasing\r\nmy legs hurt\r\nmy legs still hurt but when I\'m stand up for long time\r\nbut that deep hurt\r\nintolerable /ɪn\'tɒlərəbəl/\r\n\r\nTeacher Laura 18:43\r\nno disposition/ no energy\r\n12\r\nin my house/ at home\r\n66\r\nheight\r\nmaximum \r\noveweight\r\nWeight scale\r\n\r\nTeacher Laura 18:54\r\nbrainwashing\r\ntour option', '2026-01-14 00:58:57'),
(4, 10, 'aluno', 'sweating\r\nplay\r\nhis\r\nhonestly\r\ntell you\r\nsleepyover party\r\nsunbath\r\n\r\nYou 9:47\r\nthere werent\r\ntext / speech\r\nfrom the bottom\r\nStart all over:\r\nMake a fresh start:\r\nthat kids like\r\n\r\nYou 9:52\r\nthere are\r\nthat I sang all my life\r\nthis series has\r\nfrom the 80s\r\nthe kids watch with their father\r\nspoke\r\nnice/friendly / outgoing\r\nsimpatic\r\nlikable\r\n\r\nYou 10:06\r\nscheduled day\r\nI got why you didnt\r\nI understood\r\n\r\nYou 10:11\r\nchill/ calm /easygoing\r\nbrother\'s girlfriend = sister-in-law\r\nin SP \r\nreturned to\r\nI gave to\r\nring\r\nfrom all the family\r\n\r\nYou 10:20\r\ncrowded - lotado', '2026-01-16 13:43:13'),
(5, 16, 'aluno', 'his sister\r\nrude, strict, demand\r\nto solve\r\nfuel\r\npower/ energy\r\nit will be a diferente\r\ndifferent*\r\nquit\r\nresign\r\n\r\nYou 13:38\r\nwill not = wont > não irei\r\n2005\r\n\r\nYou 13:46\r\nShe\'s very shy to speak\r\n2x = twice\r\n\r\nYou 13:57\r\nall the people', '2026-01-20 17:01:36'),
(6, 19, 'aluno', 'onsite\r\nthere is a nice site here\r\n\r\nnearby\r\n\r\nAlice Guilhoto 8:52\r\nwe welcomed one over a main client\r\nfirst time an office\r\n\r\nYou 8:56\r\nmeet - met\r\n\r\nAlice Guilhoto 8:57\r\nat met them\r\ncoffe and water\r\nthen a gave\r\nwe had a meeting\r\n\r\nYou 8:59\r\nafter that\r\n\r\nAlice Guilhoto 8:59\r\nto discuss\r\nour going projects\r\n\r\nYou 9:00\r\nongoing - em andamento', '2026-01-21 12:03:47'),
(7, 21, 'aluno', 'What are you going to call your invention?\r\nR: NailBot\r\n\r\nHow is it going to work?\r\nR: It is going to use sensors to detect the size and shape of the nails. You just need to place your hand inside the device, and a small, safe rotary blade is going to cut your nails automatically in seconds.\r\n\r\nWhat problem is it going to solve?\r\nR:It is going to solve two main problems: it saves time for busy people and helps people who dosent like using manual clippers.\r\n\r\nHow much is it going to cost?\r\nR: It is going to cost about R$120,00\r\n\r\nWho is going to buy it?\r\nR: Busy professionals, parents who struggle to cut their children\'s nails, and elderly people are going to buy it.', '2026-01-22 17:29:15'),
(8, 38, 'aluno', 'Duble = duas vezes ex: 99 > duble nine \r\nbirthday = berf day \r\nLast name = Ultimo sobrenome\r\nI will be= futuro  ex: I wil be 19 march \r\nI was = passado ex: I was 2 thausand 7 \r\n Thausand= mil \r\nhusdred= cem \r\nhobby= ing ex: reanding books \r\n', '2026-01-31 14:17:14'),
(9, 46, 'aluno', 'House = casa  lugar\r\nHome lar\r\n\r\n\r\nso so \r\nmore or less', '2026-02-04 14:49:27'),
(10, 52, 'aluno', 'My words: ordered, confirmed, reported, rejected\r\nWell, this week I studied and worked a lot, because of and my qualification. Then, my boss ordered me to do the datas analysis of my experiments. So, yesterday I did this task and confirmed my hypothesis about chronic kidney disease about I reported on my preview research. Now I send to my boss and I hope to not rejected my datas and my ideas on the discussion about this topic.', '2026-02-14 18:02:48'),
(11, 53, 'aluno', 'Homework:\r\n1)	I wake up at 9:00\r\n2)	I have coffee at 10:00 a.m.\r\n3)	I take a shower at 11:00 a.m.\r\n4)	I brush my teeth at 11:00 a.m.\r\n5)	I go to work at 6:00 a.m.\r\n6)	I have dinner at 8:00 p.m.\r\n7)	I study at 8:00 a.m.\r\n8)	I have lunch at 12 p.m.\r\n9)	I go to sleep at 11 p.m.\r\n10)	I get up at 6:00 a.m.\r\n\r\n', '2026-02-07 13:23:13'),
(12, 62, 'aluno', 'put colocar\r\nput on - vestir/por\r\nput here\r\nput there please\r\nput on your shoes\r\ntake off\r\ntake off - tirar\r\nsee - ver / saw - viu\r\n\r\nlook here/ there\r\nstretch - esticar/ alongar ', '2026-02-13 23:32:05'),
(16, 2, 'professor', 'Bru, dont forget to enjoy the holidays', '2025-12-19 18:14:46'),
(17, 4, 'professor', 'imobiliária = real estate (not state house> sorry)', '2026-01-14 00:58:57'),
(18, 5, 'professor', 'to compete\r\nchange\r\nchange the prices\r\nto show/ to post\r\nto sell\r\n\r\nYou 14:15\r\nstinking / stain\r\n\r\ncheaper than BR\r\n\r\npassed\r\ndid in advance\r\ncar workshop\r\n\r\nturkey', '2026-01-14 18:03:11'),
(19, 6, 'professor', 'passion fruit\r\nis facing\r\nthe back\r\nof the builing\r\npan/ pot\r\npans\r\nowner\r\nit\'s ahead- mais pra frente\r\n\r\nYou 8:47\r\nknew\r\nlet / released\r\n\r\nYou 8:54\r\nto spend the New Year\'s Eve\r\nbet', '2026-01-15 12:03:01'),
(20, 7, 'professor', 'https://www.speaklanguages.com/english/phrases/more-common-expressions ', '2026-01-15 17:40:42'),
(21, 8, 'professor', 'recovered\r\nwas\r\nit was supposed to\r\npro active\r\nconfidente\r\n\r\nBruna 15:17\r\nkhan academy kids\r\n\r\nYou 15:20\r\nsutitles\r\nsubtitles\r\nspelling\r\nI can understand / recognize\r\nit\'s fresh / it\'s in my mind\r\nproxy\r\n\r\nYou 15:53\r\nhe\'s shy\r\n\r\nYou 16:01\r\ntrust confiar', '2026-01-15 19:09:37'),
(22, 9, 'professor', 'from: de > partida > ponto de origem \r\nto: para > direção > ir para algum lugar\r\nanswer key\r\n\r\nDiego Pilatti 8:45\r\nPerfect English Grammar\r\n\r\nYou 8:52\r\ngot/had/took great grade\r\n6/7 people\r\npeople are more interested\r\n\r\nYou 8:57\r\nsummary - resumo\r\n\r\nYou 9:07\r\nI\'ll not forget\r\n\r\nYou 9:15\r\nruined/ spoiled\r\nbuoy / bu:i/\r\n\r\nwas drowning - se afogando\r\nhoover\r\nhope', '2026-01-16 12:34:15'),
(23, 11, 'professor', 'came back home to solve the problem\r\nfast / quickly\r\nspeed - velocidade\r\nproposes\r\nmuch news\r\n\r\nYou 13:13\r\nalone\r\nhe helped me to\r\nsave Money\r\nme with the Money\r\nmy boss will give me\r\ntours\r\nconfidente\r\nconfident\r\n\r\nYou 13:19\r\nencouraged\r\nMay 1st\r\n\r\nYou 13:30\r\nhow was = como foi\r\nI was drunk/ I drank a lot\r\ncaban\r\n\r\nYou 13:36\r\nnative speakers\r\nmany people from the world \r\nmany/a lot = muitas\r\ngold opportunity\r\n\r\nlack of - falta de\r\npriorities\r\nthe after never comes', '2026-01-16 17:01:40'),
(24, 12, 'professor', 'dubbing\r\ncop\r\n\r\nYou 15:10\r\nshowed him\r\n\r\nYou 15:23\r\nphrases\r\npra mim você voltou\r\n\r\nYou 15:36\r\nouch\r\n\r\nYou 15:46\r\nat all- sequer/ de nenhuma maneira', '2026-01-16 19:07:53'),
(25, 13, 'professor', 'school supplies\r\nitems\r\n\r\nYou 18:19\r\nas soon as you check\r\nas lower the price will be\r\n17.000\r\n12.000\r\ndivide/split\r\nshare\r\n5km lenght\r\n\r\nYou 18:32\r\nempty / less crowded\r\nneeded to go\r\nrating\r\n\r\nYou 18:38\r\nIf I were you\r\nNew Year\'s Eve\r\nthe summer\r\n\r\nYou 18:46\r\nthere isnt parking\r\n6 classes\r\n2 classes\r\n600 6 hundred', '2026-01-19 21:55:40'),
(26, 14, 'professor', 'to make it up > pra compensar isso\r\nchill\r\nweed\r\ntry / tried\r\ndidnt work\r\nsmell\r\n\r\nYou 20:44\r\nprohibited\r\nlegal/ is free\r\nget back = volta \r\nput off = tirar roupa\r\nfelt a little pain in my legs\r\n\r\nYou 20:51\r\npatient\r\ntied\r\n\r\nLucas 21:01\r\n991451416\r\n\r\nPietra Seibt 21:03\r\n09087424957\r\n\r\nLucas 21:03\r\n82410200\r\n\r\nPietra Seibt 21:04\r\n665\r\n\r\nYou 21:10\r\nearn', '2026-01-20 00:35:47'),
(27, 15, 'professor', 'yesterday, we welcomed one over main clients\r\nfor a onsite visit. It was they first time at our  office. I met them at the reception and offered coffee and water.\r\nThen, a gave them a short tour around the space  in introduce then to the team.\r\nAfter that, we had a meeting in the conference room to discuss are ongoing projects.\r\nThey asked a few questions and shared positive feedback on the last delivery.\r\nLater, we had lunch together and at restaurant nearby. \r\nOverall, the visit went very well.\r\nProfessional, friendly and productive.', '2026-01-20 12:02:19'),
(28, 17, 'professor', 'did / do / will do', '2026-01-20 18:01:11'),
(29, 18, 'professor', 'dare\r\naddicted to\r\nfilling\r\n\r\nYou 15:16\r\nI felt very bad\r\nknees - joelhos\r\nniece\r\n30\r\nwhat will be your plans?\r\nhow will you do?\r\n\r\nYou 15:22\r\nI miss eating \r\nI\'m missing eating\r\nher house was rented\r\nshe put her house to rente\r\n\r\nYou 15:29\r\nrised/ increaed\r\ngrew\r\nincreased\r\nread / red/\r\n\r\nYou 15:35\r\ni feel useless\r\nfault\r\nguilty\r\n\r\nYou 15:41\r\ntrial', '2026-01-20 19:02:37'),
(30, 20, 'professor', 'I\'m still organizing \r\nyet> negative\r\nat the moment\r\ncame / got\r\nI\'ve just arrived\r\nof / from the gym\r\nas I said / as I told you\r\n\r\n\r\nAs far as I\'m concerned\r\nI would argue that…\r\nNot only that, but…\r\nLet’s not forget that…\r\nNevertheless,... > contudo\r\nEven so,... > mesmo assim\r\nI strongly believe that\r\nWithout a doubt,…\r\n\r\nYou 18:29\r\nhow many stars and planets > Always plural \r\nother lives // lifes\r\nET\r\npyramids\r\nwas built - foi construída\r\ntools - ferramentas\r\nstones\r\nabove each other\r\nrestricted zone\r\n\r\nrobots\r\nthemselves\r\nall the night / all night long\r\n\r\non Earth\r\nWhat I mean\r\nbia/ biased \r\nI was 20 years old\r\npeople were happier\r\nchildhood\r\n\r\nI\'d try to live\r\ntheme /topic\r\nbia/ biased', '2026-01-21 21:56:36'),
(31, 22, 'professor', 'https://test-english.com/grammar-points/a1/past-simple-negatives-questions/ homework', '2026-01-22 19:02:08'),
(32, 23, 'professor', 'It\'s Queen\'s song which it\'s called\r\nwhole\r\n\r\nTathiane S 9:50\r\nwhole\r\n\r\nYou 9:50\r\n/rol/\r\n\r\nTathiane S 9:51\r\nlaughed\r\n\r\nYou 9:51\r\nlaughed /léft/\r\nlaugh\r\nsafe\r\nforecast\r\nit seems\r\nI learned\r\n\r\nTathiane S 9:55\r\nstill\r\n\r\nYou 9:55\r\nyet > negative\r\nare you still here?\r\nhave you finished the house yet?\r\ncleaning\r\nsong / music\r\nmusics > nowadays musics\r\nsongs\r\ntracks\r\n\r\nYou 10:01\r\nget down to business\r\n20 07 \r\n20 oh seven \r\n2 Thousand 7\r\nmainly - princiaplmente\r\nfar away places\r\nwe took', '2026-01-23 13:44:05'),
(33, 24, 'professor', 'cable car\r\nthe waiting is\r\ncrowded\r\namusement park\r\nrides\r\nshe\'s afraid\r\nit will be\r\n\r\nYou 13:39\r\nLet\'s get down to business\r\n\r\nYou 13:59\r\nwho whom', '2026-01-23 17:04:28'),
(34, 25, 'professor', 'board games\r\nbuild - construir\r\nI won\r\n\r\nYou 14:13\r\ndeck of cards\r\nluck or reversal\r\n\r\nYou 14:25\r\nwith one card\r\nfor each player\r\nforehead\r\nsecond round\r\nit\'s go on\r\nwhat you said\r\nyou lose/ get lost\r\nscrews up\r\nget screwed\r\nscroll\r\ntaught her - ensinei\r\ntaught > teach\r\n\r\nYou 14:31\r\nget sick\r\nget full - se enche\r\nachieve\r\nget\r\n\r\nYou 14:42\r\nshe doesnt work\r\ndaily routine', '2026-01-26 18:06:42'),
(35, 26, 'professor', 'On Friday pedro and I went to the beach, on saturday in the morning pedro and I saw the sunrise after i ran and went to thhe beach why my mom and my mother in law and my father and pedro saw the guaratuba bridge the build and in the afternoon i ate a ice cream and on sunday we returned to curitiba and we got a long traffic but ok, and you teacher what did you do in your weekend?\r\n\r\nLucas 20:39\r\nwell, This was my last weekend in vacation at school, and I enjoy to rest a lot and play and watch a lot too. On sunday I went to play soccer , but the field was terrible because this I felt a litlle pain in my anckles and my knees, for my this is so sad because i like so much the team but I think i will can play more in this field\r\n\r\nYou 20:42\r\nknown\r\nit\'s known in\r\neach scoop\r\nholes\r\n\r\nYou 20:49\r\nsummarize\r\nspeech/speak/talk\r\n\r\nLucas 20:49\r\nThis weekend your boyfriend birthday 30 years, and there is a festival in your city, you enjoeyd a very delicious barbecue, and the weekend was very fun\r\n\r\nPietra 20:50\r\nwas my lucas birthday an dnow its 30 lucas family came to jundiai arrived friday night there is a festival here and saturday very nice and we made a barbecue it was pretty cool and lucas and cousin play for many times 5 or 6 hours play too much and on sunday family comeback to curitiba and clean the house and in the afternoon we went to festival again\r\n\r\nYou 20:53\r\nthere were 2\r\ntalks\r\nlectures\r\nI hope\r\ntimetable\r\n\r\nYou 21:03\r\ntwo weeks delayed\r\nI could get\r\n\r\nYou 21:13\r\nfell / hit\r\ndiscuss/argue/fight\r\n\r\nYou 21:18\r\nmain - principal\r\n\r\nYou 21:24\r\ngive in / give way\r\nsubject\r\nbury - enterrar\r\nI would be judged by\r\n\r\nYou 21:31\r\ngot on tie\r\nscored the winner goal', '2026-01-27 00:33:14'),
(36, 27, 'professor', 'Hi \r\nI\'m having a trouble\r\nacessing the CIM plataform\r\nEvery time I try to log in\r\nI get in air message saying:\r\naccess denied.Invalid credentials\r\nI\'ve already tried resetting my password\r\nbut the issue persists.\r\nI\'m using google chrome on the windows\r\n11 laptop.\r\nThis started happening this morning\r\naround 9:00am \r\nCould you please help me check watch \r\nmight be wrong?\r\nThank you very much.', '2026-01-27 12:01:12'),
(37, 28, 'professor', 'they do a party\r\nwent to a rest. to have lunch > ate / had lunch\r\nthere are rare cars\r\nreleased\r\nwas killed\r\nferris wheel\r\nrode - já andei\r\nride\r\n\r\nYou 13:39\r\nfreezing\r\ntranquil, easy, chill, easygoing\r\nthe view\r\nI\'m used to it\r\nnicer / more adventurous \r\ncrowd > multidão // crowded > cheio de gente\r\n\r\nYou 13:45\r\ngo on tours\r\ndays are more\r\nI will not be here\r\n\r\nYou 13:51\r\ndidnt pause\r\nbusier - mais agitado\r\nsuppliers\r\nworkers\r\nprofessionals\r\nbudget - orçamento\r\nconcreting the slab\r\n\r\nYou 13:56\r\ninterrupt / disturb/ bother\r\nworktime\r\nI\'ve already solved\r\nmove on', '2026-01-27 17:00:33'),
(38, 29, 'professor', 'I\'m sick\r\nnot too much\r\ntoo much\r\na lot of work\r\nrecovering\r\ndidnt have\r\n\r\nYou 20:27\r\nsome things\r\n\r\nYou 20:38\r\nI\'m not in the mood\r\n\r\nYou 20:49\r\nnearby - por perto\r\nbuy - bye- by\r\nwelcomed\r\nmain client \r\nmain - principal\r\nonsite\r\nI met them\r\nmeet > met\r\n\r\nYou 20:54\r\nshort tour\r\nintroduce ... to\r\ndiscuss\r\nongoing - em andamento\r\n\r\nYou 20:59\r\nasked\r\nask/t/\r\nshared\r\npositive\r\non the last delivery', '2026-01-28 00:03:14'),
(39, 30, 'professor', 'spent her birthday\r\nsend flowers to her\r\nwill be\r\ncalmed me\r\nmy leaving\r\nsince July\r\nneeds to be judged\r\n\r\nYou 13:39\r\nI didnt apply for it\r\nwithout the need of going to\r\npossible = possível \r\npossibility = possibilidade\r\nwe\'re late in 60 days\r\nin the previewed cost\r\n\r\nYou 13:45\r\nI have already followed\r\nI\'ve followed\r\neach brick\r\ndont lose - não perde\r\ncertain / sure\r\ngrow/rise/inscrease\r\nproperties\r\nsimilarities\r\ngoal / aim / target / objective\r\nbreak this - quebrar isso \r\nbroke this - quebrou isso\r\nachieve / conquer / realize\r\n\r\nYou 13:54\r\nclay - barro\r\nsubfloor\r\nslab - laje\r\nbefore traveling/ coming here\r\nmainly - principalmente\r\nearthwork\r\n\r\nYou 13:59\r\ndrilling - perfuração\r\nloss', '2026-01-28 17:01:21'),
(40, 31, 'professor', 'https://test-english.com/grammar-points/a1/past-simple-regular-irregular/2/ > homework', '2026-01-28 18:16:52'),
(41, 32, 'professor', 'lecture / talks\r\nwere kidding/joking\r\nseem\r\nseemed\r\nwhile I was there\r\nuntil I was there\r\nunderstood\r\nhow the education helps\r\nmean\r\n\r\nLucas 20:39\r\nmeanig\r\n\r\nYou 20:39\r\nmeaning\r\n\r\nYou 20:42\r\ndizzle\r\n\r\nYou 20:42\r\ndrizzle\r\n\r\nYou 20:42\r\nlight rain\r\nlighting\r\nseaside\r\nNews\r\na News\r\nhips\r\nwith it\r\n\r\nYou 20:47\r\nafter the running\r\nstretch\r\nget down to business\r\n\r\nYou 21:03\r\nchoice - escolha\r\nchoose / chose / chosen\r\n\r\nYou 21:15\r\nassign tasks: atribuir\r\n\r\nLucas 21:22\r\nLet´s stop the talks and let´s get the ball rolling\r\n\r\nPietra 21:22\r\ntoday my boss tell me the get the ball rolling on the next experiment\r\ntold me*\r\n\r\nLucas 21:24\r\nOk we now he did a mistake and the pass but let´s give soemone the benefit of the doubt this time\r\n\r\nPietra 21:24\r\nthe doctor said about the inflammation its easy and i give someone the benefit of the doubt\r\nnow I need to go the extra mile if I want to pass the next public exam\r\n\r\nLucas 21:26\r\nWe need go  the extra mile for allthings finish today.\r\n\r\nYou 21:26\r\nneed to\r\n\r\nPietra 21:27\r\nwhen I explain a concept for my students i try to hit the nail on the head in the class\r\n\r\nYou 21:27\r\nbe done / be finished\r\n\r\nLucas 21:27\r\nWe need to go the extra mile for be finished today\r\n\r\nPietra 21:28\r\ni always cry in the heat of the moment in the discussion\r\nduring the class\r\nwhen I explain a concept for my students i try to hit the nail on the head during the class\r\ni always cry in the heat of the moment during the discussion\r\n\r\nLucas 21:30\r\nMy children hit the nail on the head the name about the exercise\r\n\r\nPietra 21:30\r\nwe turned friends in our first class it was a piece of cake for me\r\n\r\nYou 21:31\r\nbecome - became\r\n\r\nLucas 21:31\r\nSorry for that in the last week I did in the heat of the moment\r\n\r\nPietra 21:31\r\nwe became friends in our first class it was a piece of cake for me', '2026-01-29 00:33:11'),
(42, 33, 'professor', 'Subject: VPN access - access denied\r\n\r\nDear Support IT team,\r\n\r\nI\'m having a trouble with VPN access because\r\nit is saying \"Access denied\". I used the VPN normally\r\nyesterday.\r\n\r\nI use the VPN to access the client environment. \r\n\r\nI attached the screenshot with the error message.\r\n\r\nCould you might help me check my credencials?\r\n\r\nThank you,\r\nFabiane', '2026-01-29 12:02:20'),
(43, 34, 'professor', 'Nicky> homework\r\nWrite about your weekend: put everything in the past', '2026-01-29 18:03:28'),
(44, 35, 'professor', 'I\'m bored to stay\r\nthere was a tornado/ wind\r\nblowing\r\nunder the weather - meio doente\r\n12\r\n\r\nYou 15:13\r\nshe\'s pregnant \r\npregnancy\r\nbelly\r\ngrowing\r\nstuffed\r\nstew - estufada\r\nwill be the Godparents\r\nmad - louca \r\nson / daughter / kids=children\r\nas long as she\r\n\r\nYou 15:19\r\nbig child / she\'s 9\r\nspoiling\r\nis better to have a baby\r\ncheaper\r\n\r\nYou 15:27\r\nGoddaughter\r\n\r\nYou 15:36\r\nher husband\r\nhas\r\n3 children\r\nshe wanna get pregnant\r\n\r\nYou 15:53\r\ntheater\r\ntheatre\r\n\r\nYou 15:58\r\nthose boots there too', '2026-01-29 19:04:35'),
(45, 36, 'professor', 'I\'m not sure\r\nwas\r\nwas 8 months ago\r\nthe tours are crowded\r\nhear me?\r\n\r\nthiago.zattoni 13:38\r\nyes\r\n\r\nYou 13:40\r\norder/sequence\r\ncheck the weather\r\nforecast\r\nhotter\r\nthese days are\r\nless crowded\r\nfrom Wed\r\non Mon, on Wed\r\nI already went\r\nthere are left\r\nleft those tours\r\nnear BC\r\nmaybe\r\n\r\nYou 13:47\r\nwith hurry\r\nconcrete\r\ninner pool\r\ninside the house\r\nisnt heated or covered\r\nwe didnt have\r\nsolve\r\nplumber\r\n\r\nYou 13:52\r\ncontracted/ hired\r\nthat I drew\r\nincompetent, inept, unqualified\r\ndidnt do the correct draw\r\nin a certain way\r\nassumed\r\nlate at night\r\nit passes/ passed\r\nin her land\r\nhavent used \r\ndidnt use\r\nat least\r\nspace/place\r\nroom\r\nturn on\r\n\r\nYou 13:59\r\nbackyard / garden\r\nwould lose\r\nI\'ll lose inside \r\nI lost inside > past\r\nduties', '2026-01-30 17:04:07'),
(46, 37, 'professor', 'https://test-english.com/vocabulary/a2/words-with-prepositions-a2-english-vocabulary/ homework', '2026-01-30 19:03:46'),
(47, 39, 'professor', 'went off\r\nInflatable toy: Termo geral para qualquer brinquedo inflável.\r\nBouncy castle: O clássico pula-pula inflável.\r\nBounce house / Inflatable bouncer: Termos comuns para estruturas infláveis de pular.\r\nInflatable slide: Escorregador inflável.\r\n\r\nYou 9:22\r\n2 packs\r\ntea spoon\r\n\r\nYou 9:48\r\nIf you ask me…\r\nIt seems to me that…\r\nNot only that, but...\r\nWhat’s more,...\r\n\r\nYou 9:54\r\nbeings - seres \r\nbe-ser\r\ndemons', '2026-02-02 13:00:58'),
(48, 40, 'professor', 'will pass fast\r\nforest / woods\r\nwe were\r\nwe could climb\r\nwe were able to climb\r\neveryone have climbed\r\nthe person stays\r\ngets very tired\r\n\r\nYou 18:13\r\nuphill - subida\r\nget at the top\r\nview/ landscape\r\nwalks more slowly\r\n\r\nYou 18:19\r\nwe were able \r\nexpand / spend\r\nJog / Jogging:\r\nhe\'s younger\r\npeople that were born\r\nin\r\nthe life knowledge\r\npassed through\r\nsigns\r\n\r\nYou 18:25\r\nspecialist\r\nLeo\r\nPiscis\r\npisces*\r\nshe prefered\r\nshe will prefer a trip\r\nmake a trip = travel \r\ntrip = viagem\r\ntravel = viajar\r\nchose/ prefered\r\n\r\nYou 18:34\r\nget this day off\r\nbirthday - nascimento\r\nanniversary\r\nwe were able \r\nexpand / spend\r\ndeadline : prazo\r\n\r\nYou 18:51\r\nput together / join\r\nthese two things\r\ni was able/ we were able \r\nexpand / spend\r\nexpanded\r\nspent more time\r\n\r\nYou 18:58\r\nwill wear more relaxed/informal/casual clothes', '2026-02-02 22:02:17'),
(49, 41, 'professor', 'long time friend\r\ncomplain\r\nbeginning of the year\r\n45 of the second half\r\nget down to business\r\n\r\nYou 19:40\r\ncharacter\r\ntook pill for my\r\ntake/took\r\n\r\nMurilo Marins - MOTIVA 20:00\r\nGiovanna Malerba Del Passo Silva\r\n\r\nYou 20:01\r\nput off/ took off\r\ntook to\r\nregristy office\r\ntook the paper to registry\r\n\r\nYou 20:31\r\nsleep\r\ntake a nap', '2026-02-02 23:32:41'),
(50, 42, 'professor', 'Lucas, check those pronunciation \r\nofficials - əˈfɪʃəlz \r\nconfusion - kənˈfjuʒən\r\nrejected - rɪˈʤɛktɪd \r\nresist - rɪˈzɪst\r\ncheck - ʧɛk  ', '2026-02-04 18:41:12'),
(51, 43, 'professor', 'odos os dias, fazendeiros precisam aplicar diferentes técnicas no campo.\r\nEles adicionam fertilizantes no solo e consertam maquinas quando não estão funcionando bem.\r\ndurante a época de plantio, eles semeiam as sementes e depois cultivam as culturas com cuidado.\r\n\r\nLeandro Hipolito 18:32\r\nquando as plantas crescem demais, eles aparam e removem as partes danificadas.\r\nantes da colheita, produtores geralmente tratam as plantas e protegem contra as doenças.\r\nem alguns casos, eles aplicam calcário no solo para aumentar a fertilidade do solo.\r\ndepois disso, eles rotulam os containers e repetem o processo em outras áreas.\r\n\r\nLeandro Hipolito 18:37\r\nEssas ações fazem parte da gestão diária da fazeda e ajudam aumentar a produtividade e qualidade das culturas.', '2026-02-03 21:58:01'),
(52, 44, 'professor', 'na fazenda tem muitas culturas diferentes crescem juntas.\r\nsoja,milho e trigo são muitos comuns em grandes campos my friend\r\nfazendeiros também plantam vegetais  como: alface, espinafre,tomate e pepino.\r\n\r\nuser 18:46\r\nem menores áreas,  eles cultivam cenoura,cebola, alho e quiabo.\r\nalgumas fazendas produzem aboboras e mandiocas e leva no mercado local(feirinha)\r\ncada  planta precisa  de solo corrigido, agua e cuidado.\r\n\r\nuser 18:53\r\ndurante essa etapa, fazendeiros checam as plantas todo dia para ver se estão saudáveis.\r\nessas culturas são importante para alimentação e para a rconomia local.\r\nbons manejos ajudam os fazendeiros para obter melhores resultados  e plantas fortes.', '2026-02-03 21:58:14'),
(53, 45, 'professor', 'sand\r\nallow\r\nallowed\r\nallow/ deny\r\nget down to business\r\n\r\nYou 20:10\r\nwindow shopping\r\n\r\nYou 20:16\r\nprocess yield\r\n\r\nMari 20:48\r\nTrouble\r\n\r\nMariele Fernandes 20:48\r\nTrouble\r\n\r\nMari 20:49\r\nAcsessing\r\n\r\nMariele Fernandes 20:49\r\nacsessing\r\n\r\nYou 20:49\r\naccessing\r\n\r\nMariele Fernandes 20:49\r\nalmost\r\naccess\r\nSRM\r\n\r\nYou 20:50\r\nCRM\r\nlog in\r\nlogin\r\n\r\nMariele Fernandes 20:54\r\nSeeing\r\n\r\nMari 20:54\r\nAir mensagem saying\r\n\r\nYou 20:54\r\nsaying\r\n\r\nMari 20:55\r\nAccess denyed\r\n\r\nYou 20:55\r\naccess denied\r\n\r\nMariele Fernandes 20:55\r\ninvalid credencials\r\n\r\nYou 20:56\r\nI\'ve already tried\r\n\r\nMariele Fernandes 20:57\r\nreseting\r\n\r\nYou 20:57\r\nresetting\r\n\r\nMariele Fernandes 20:57\r\npassword\r\n\r\nMari 20:58\r\nPassword\r\n\r\nYou 20:58\r\nbut the issue\r\npersists\r\non\r\nstarted happening\r\n\r\nMariele Fernandes 21:00\r\nhappening\r\n\r\nYou 21:02\r\nhelp me\r\nmight\r\nbe wrong\r\nwhat might be wrong\r\nmight - pode/possivelmente', '2026-02-04 00:04:34'),
(54, 47, 'professor', 'Pi, check the pronunciation\r\nordered - confirmed \r\nreported - rejected \r\nˈɔrdərd - kənˈfɜrmd\r\nˌriˈpɔrtəd - rɪˈʤɛktɪd', '2026-02-04 18:54:02'),
(55, 48, 'professor', 'complained\r\nsimulatrs\r\nis better than\r\n\r\nYou 8:39\r\nIf you ask me…\r\nIt seems to me that \r\nAs far as I\'m concerned\r\nLet’s not forget that\r\nIt’s also worth mentioning that\r\nrained\r\n\r\nYou 8:44\r\nthose hours/ that time\r\nDespite that\r\nEven so,\r\nWithout a doubt,… \r\nIt’s undeniable that', '2026-02-05 12:02:22'),
(56, 49, 'professor', 'excuse\r\nto see\r\nto talk\r\nbroke up\r\nconfidence\r\nI know him for 3 years\r\nimproved / rose\r\nhe has\r\ngot better\r\nfor / since \r\nfor x years\r\nsince 2023\r\nfor almost\r\n\r\nYou 11:39\r\nspeak\r\nlaugh\r\n\r\nYou 12:15\r\nage group\r\nage range', '2026-02-05 15:31:14'),
(57, 50, 'professor', 'uncharted\r\n\r\nYou 15:08\r\nas you choose\r\nas you make your choices\r\ndepending\r\nsafe - seguro\r\nsave\r\nsorry to interrupt\r\n\r\nYou 15:59\r\ntag\r\n\r\nBruna 16:01\r\ntadhg', '2026-02-05 19:03:54'),
(58, 51, 'professor', 'I delayed\r\nfired\r\ntuesday\r\nthrusday\r\nwe would be fired\r\nthe owner of the company\r\nhe sent me a message and he told \r\nhe would talk to my coworker\r\n\r\nYou 18:08\r\nfarewell\r\nbefore firing him\r\nneeded to pretend\r\nthat I didnt know\r\nso far- até então\r\nlooking for/ searching\r\nthere arent people for the vacancy\r\nthey didnt choose\r\n\r\nYou 18:14\r\nneeded\r\nto be able to work\r\nfor them\r\nhistorical\r\nit wasnt\r\nfair\r\n\r\nYou 18:19\r\nhe likes to cut corners \r\n\r\nanxious\r\ncriticized\r\nburocratic\r\ntelling/ saying\r\nboring\r\nI feel bored using it\r\n\r\nYou 18:24\r\nupdated\r\nI\'m used to use\r\nperfectionist\r\nI get annoyed\r\ncharged\r\n\r\nYou 18:29\r\nnext episodes\r\nhe needs to do\r\nreliable person/worker\r\nHR\r\nhuman resources\r\n\r\nYou 18:39\r\nfield / area\r\nbranche\r\narchaic\r\noutdated\r\nexceeded\r\ncourse\r\n\r\nYou 18:46\r\nSystems Analysis and Development\r\nas mine\r\ndegree certification\r\n\r\nYou 18:52\r\ndelivery/ hand in: entregar\r\nit doesnt/ didnt work\r\nlazy\r\ntip/ suggestion\r\nmajor course\r\nstrategy\r\n\r\nprove\r\nit\'s used\r\nit\'s Worth it', '2026-02-05 22:01:54'),
(59, 52, 'professor', 'Great job, Pi! ', '2026-02-14 18:02:48'),
(60, 54, 'professor', 'it gives much work\r\nI got proud of\r\nshe told me\r\nshe said\r\nthat I\'ll change\r\nNicky\'s docs\r\n\r\nYou 15:09\r\nto my own house\r\n1600\r\nhe\'s out of his mind\r\nhe\'s insane\r\npack\r\npacking\r\nboxing\r\nmore expensive\r\na bit more\r\napplied\r\n\r\nYou 15:14\r\ngets right\r\nrock the boats\r\nappliences\r\nchest of drawers\r\nthere are somethings\r\nwardrobe\r\n\r\nYou 15:22\r\nwe\'re not scared/nervous\r\ni dont want\r\nin last case\r\nis the deadline\r\nghosted -desapareceu\r\n\r\nYou 15:28\r\nshopaholic\r\ndish washer\r\ndish/dishes - louça\r\nfrom tap\r\ntapped water\r\nhe gave\r\n\r\nYou 15:46\r\nfancy\r\nelegant\r\ngrapes juice\r\n\r\nYou 16:00\r\nthe house has\r\nthere is\r\nmattress', '2026-02-10 19:01:03'),
(61, 55, 'professor', 'in the weekend\r\nrested\r\nLet me pick/grab/take a glass of water\r\nmother\'s house\r\ncame\r\nmaster\'s thesis committee\r\nmaster\'s thesis defense\r\nwe\'re all in the same boat\r\n\r\nEliana Chubaci 11:15\r\nTiny- minusculo\r\n\r\nYou 11:29\r\ndelivery\r\n\r\nYou 11:35\r\nadministrators\r\nbusinesspeople\r\nentrepreneurs', '2026-02-11 15:02:56'),
(62, 56, 'professor', 'in the weekend\r\nrested\r\nLet me pick/grab/take a glass of water\r\nmother\'s house\r\ncame\r\nmaster\'s thesis committee\r\nmaster\'s thesis defense\r\nwe\'re all in the same boat\r\n\r\nEliana Chubaci 11:15\r\nTiny- minusculo\r\n\r\nYou 11:29\r\ndelivery\r\n\r\nYou 11:35\r\nadministrators\r\nbusinesspeople\r\nentrepreneurs', '2026-02-11 15:03:05'),
(63, 57, 'professor', 'I had\r\ncried\r\nget used\r\nshy\r\nthat - que\r\naway from\r\n\r\nYou 13:38\r\nwho cried more\r\nduring/in the break\r\ncomfort\r\nthe nearest\r\n\r\nYou 13:44\r\nrested\r\nfor this reason\r\ntaht\'s why\r\nbecause of it\r\nI liked\r\nhottest day in the week\r\nget a shadow\r\nyou got cold\r\naltitude\r\nslab\r\n\r\nYou 13:49\r\nseemed\r\nbig/bigger / large\r\ngarage/ machine house/\r\nperception\r\ntook 3 years\r\nto be finished - pra ser terminado\r\ntook my family there / to know there\r\ndiscussed\r\nmade the best\r\nis getting\r\nis becoming\r\nshe\'s calling, I need to answer\r\n\r\nYou 13:54\r\nare seeing\r\nall Worth it\r\nworth - valer\r\nowe - dever\r\nwould be - seria \r\nwill be - será\r\nmiddle-size', '2026-02-11 16:58:41'),
(64, 58, 'professor', 'I was waiting\r\nalmost fell asleep\r\nfall asleep\r\nwe got the house\r\nin the bags\r\ndaily clothes\r\ntalked to Nicky - conversei com ele\r\n\r\nYou 15:12\r\nhis mother\r\nwardrobe\r\nwhen it got it\r\nreal estate\r\nestate agency\r\ncalled me to say\r\nin those sit\r\nthat we see\r\nthat we\'re\r\ngrowing\r\nimproving\r\npersonal growth\r\n\r\nYou 15:17\r\nI\'m not stressed\r\nhit the sack\r\nsleep heavy\r\nI would be happier \r\nif it was my own house\r\ndont give problems\r\nowner\r\nextend the contract\r\n\r\nYou 15:23\r\n4 streets away\r\nI didnt take/get\r\nit was raining\r\n\r\nYou 15:31\r\nit succeeded', '2026-02-12 19:03:13'),
(65, 59, 'professor', 'migraine\r\nsome times\r\nI get/am sick too\r\nget/catch\r\nexit\r\n\r\nYou 18:07\r\nschedule/ plan \r\nI get\r\nafraid\r\nhe saw a news\r\nwastewater system\r\nis throw\r\nin the water of the sea\r\nin the sea water\r\n\r\nYou 18:14\r\nfarther\r\ntake sunbath\r\nsunscreen / sunblock\r\nskin - pele\r\nlow season\r\n\r\nYou 18:20\r\ntwenty-third/three\r\nspend ... days\r\nreally enjoy\r\ninappropriate water\r\ntouching, hitting\r\njellyfish - água viva\r\n\r\nYou 18:26\r\nI have fear \r\nI\'m afraid\r\nof\r\nthere is a lot of water\r\nneeds\r\ndo their needs\r\nmuch worse\r\n\r\nYou 18:31\r\nget into\r\ncousin\r\nher wife\r\ncouple\r\n\r\nYou 18:41\r\nshow off \r\ncompetences\r\nwants to charge more\r\nwants to demand more\r\nconflict\r\namong them\r\nbetween them\r\n\r\nYou 18:50\r\naffected\r\nhe\'s suing\r\nmad\r\nlabor market\r\ncompetitors\r\n\r\nYou 18:57\r\nidentified myself', '2026-02-12 22:01:25'),
(66, 60, 'professor', 'didnt take a toy\r\ndidnt want to take\r\nintroduced/ integrated\r\nlet\r\ngot off\r\nreplaced by\r\n\r\nYou 13:41\r\ngetting in order\r\nwill get in order\r\nthings are getting order\r\nmake it up\r\nagree/ deal\r\nin advance\r\n\r\nYou 13:46\r\nschedule\r\nI had\r\nit was ok \r\nI had excuse to speed up\r\nlunchbreak / lunchtime\r\nI didnt have\r\ndrink a glass/cup of milk\r\n\r\nYou 13:52\r\nI police more\r\n\'big deal\'\r\nI went to a very good restaurant\r\nfine dining / high gastronomy\r\ncost-benefit\r\nfor her\r\nI think/ I guess/ I Suppose\r\nlobster - lagosta \r\nsea food\r\n\r\nYou 13:58\r\nonce per year - 1x por ano\r\n1x once \r\n2x twice', '2026-02-13 17:00:23'),
(67, 61, 'professor', 'Manufacturing Engineering // Production Engineering\r\n\r\nnice Holiday', '2026-02-13 22:04:44'),
(68, 63, 'professor', 'like that ', '2026-02-14 14:46:20');

-- --------------------------------------------------------

--
-- Estrutura para tabela `anotacoes_visualizacoes`
--

CREATE TABLE `anotacoes_visualizacoes` (
  `id` int(11) NOT NULL,
  `anotacao_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `data_visualizacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `anotacoes_visualizacoes`
--

INSERT INTO `anotacoes_visualizacoes` (`id`, `anotacao_id`, `professor_id`, `data_visualizacao`) VALUES
(1, 52, 22, '2026-02-14 18:02:48');

-- --------------------------------------------------------

--
-- Estrutura para tabela `arquivos_visiveis`
--

CREATE TABLE `arquivos_visiveis` (
  `id` int(11) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `conteudo_id` int(11) NOT NULL,
  `visivel` tinyint(1) NOT NULL DEFAULT 1,
  `data_modificacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `aulas`
--

CREATE TABLE `aulas` (
  `id` int(11) NOT NULL,
  `titulo_aula` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_aula` date NOT NULL,
  `horario` time NOT NULL,
  `turma_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `recorrente` tinyint(1) NOT NULL DEFAULT 0,
  `dia_semana` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `aulas`
--

INSERT INTO `aulas` (`id`, `titulo_aula`, `descricao`, `data_aula`, `horario`, `turma_id`, `professor_id`, `recorrente`, `dia_semana`) VALUES
(34, 'General', '', '2025-10-24', '21:30:00', 12, 22, 0, NULL),
(35, 'Conversation', 'http://iteslj.org/questions/freetime.html', '2025-10-31', '21:30:00', 12, 22, 0, NULL),
(36, 'Aulas Jorge', '', '2025-10-21', '11:30:00', 11, 22, 1, 'tuesday'),
(37, 'Aulas Jorge', '', '2025-10-28', '11:30:00', 11, 22, 1, 'tuesday'),
(38, 'Aulas Jorge', '', '2025-11-04', '11:30:00', 11, 22, 1, 'tuesday'),
(39, 'Aulas Jorge', '', '2025-11-11', '11:30:00', 11, 22, 1, 'tuesday'),
(40, 'Aulas Alice Guilhoto', '', '2025-11-03', '07:00:00', 15, 22, 1, 'monday'),
(41, 'Aulas Alice Guilhoto', '', '2025-11-10', '07:00:00', 15, 22, 1, 'monday'),
(42, 'Aulas Alice Guilhoto', '', '2025-11-17', '07:00:00', 15, 22, 1, 'monday'),
(43, 'Aulas Alice Guilhoto', '', '2025-11-24', '07:00:00', 15, 22, 1, 'monday'),
(44, 'Aulas Kleber', 'https://www.conversationstarters.com/questions-by-topic/work.htm \r\nhttps://www.newsinlevels.com/products/iceland-and-women-level-1/', '2025-11-10', '18:00:00', 18, 22, 1, 'monday'),
(45, 'Aulas Kleber ', '', '2025-11-17', '18:00:00', 18, 22, 1, 'monday'),
(46, 'Aulas Kleber ', '', '2025-11-24', '18:00:00', 18, 22, 1, 'monday'),
(47, 'Aulas Kleber', 'https://www.youtube.com/watch?v=0x1WRY4fvz4 ; https://www.englishclub.com/pronunciation/minimal-pairs-i-ee.php; http://iteslj.org/questions/jobs.html > foco na pronúncia', '2025-12-02', '18:30:00', 18, 22, 1, 'monday'),
(48, 'Aulas Boss Ladies Club', '', '2025-11-18', '19:30:00', 24, 22, 1, 'tuesday'),
(49, 'Aulas Boss Ladies Club', 'Goals: http://iteslj.org/questions/goals.html ; Simple present: https://test-english.com/grammar-points/a1/present-simple/; adverbs', '2025-11-25', '19:30:00', 24, 22, 1, 'tuesday'),
(50, 'Aulas Boss Ladies Club', '', '2025-12-02', '19:30:00', 24, 22, 1, 'tuesday'),
(51, 'Aulas Boss Ladies Club', '', '2025-12-09', '19:30:00', 24, 22, 1, 'tuesday'),
(52, 'Aulas Boss Ladies Club', 'https://test-english.com/grammar-points/a1/present-simple/ ; https://test-english.com/grammar-points/a1/present-simple-forms-of-to-be/ ; https://www.newsinlevels.com/products/family-saves-bear-level-1/', '2025-11-11', '19:30:00', 24, 22, 0, NULL),
(53, 'Aulas Ana e Diego', '', '2025-11-21', '08:30:00', 30, 22, 1, 'friday'),
(54, 'Aulas Ana e Diego', '', '2025-11-28', '08:30:00', 30, 22, 1, 'friday'),
(55, 'Aulas Ana e Diego', '', '2025-12-05', '08:30:00', 30, 22, 1, 'friday'),
(56, 'Aulas Ana e Diego', '', '2025-12-12', '08:30:00', 30, 22, 1, 'friday'),
(57, 'Aula Ana e Diego', 'Reading: https://www.newsinlevels.com/products/amazons-big-ai-data-center-level-3/ \r\nConversation: https://www.conversationstarters.com/questions-by-topic/work.htm \r\nGrammar: phrasal verbs', '2025-11-14', '08:30:00', 30, 22, 0, NULL),
(58, 'Aulas Jorge Pontes', 'https://docs.google.com/document/d/1_EHl1T2eWjmlRRzfTUejuApCSnxBx7VgMGlKwhCQmZk/edit?usp=sharing', '2025-12-02', '11:30:00', 11, 22, 1, 'tuesday'),
(59, 'Aulas Jorge Pontes', 'https://jeopardylabs.com/play/348fba7e96 ; http://iteslj.org/questions/lifesofar.html', '2025-12-09', '11:30:00', 11, 22, 1, 'tuesday'),
(60, 'Aulas Jorge Pontes', 'Join Zoom Meeting\r\nhttps://us06web.zoom.us/j/88043030586?pwd=C9ofB9QWHjuazYDUdrYdPZetKlm6ji.1\r\nMeeting ID: 880 4303 0586\r\nPasscode: 047615', '2025-12-19', '07:30:00', 11, 22, 1, 'tuesday'),
(61, 'Aulas Jorge Pontes', 'http://iteslj.org/questions/lifesofar.html', '2025-11-25', '11:00:00', 11, 22, 1, 'tuesday'),
(62, 'Aulas Kleber', 'Life so far: http://iteslj.org/questions/lifesofar.html; pronunciation: https://www.englishclub.com/pronunciation/minimal-pairs-i-ee.php', '2025-11-26', '18:00:00', 18, 22, 0, NULL),
(63, 'Aula Manu', 'Disney questions in the past', '2025-11-26', '15:15:00', 17, 22, 0, NULL),
(65, 'Aulas Pietra e Lucas', 'https://test-english.com/grammar-points/a2/asking-questions-in-english/ ; http://iteslj.org/questions/mindbodyhealth.html', '2025-12-08', '20:30:00', 20, 22, 1, 'monday'),
(66, 'Aulas Pietra e Lucas', '', '2025-12-15', '20:30:00', 20, 22, 1, 'monday'),
(68, 'Aulas Pietra e Lucas', 'Airport: http://iteslj.org/questions/airplanes.html; vocabulary: https://test-english.com/vocabulary/b1-b2/air-travel-b1-b2-english-vocabulary/ + exercises', '2025-11-26', '20:30:00', 20, 22, 1, 'wednesday'),
(69, 'Aulas Pietra e Lucas', 'https://test-english.com/vocabulary/b1-b2/air-travel-b1-b2-english-vocabulary/ ; http://iteslj.org/questions/airplanes.html; https://www.youtube.com/watch?v=0z0E30uOMbs', '2025-12-03', '20:30:00', 20, 22, 1, 'wednesday'),
(70, 'Aulas Pietra e Lucas', 'https://test-english.com/vocabulary/b2/feelings-and-emotions-b2-english-vocabulary/ + http://iteslj.org/questions/personality.html', '2025-12-10', '20:30:00', 20, 22, 1, 'wednesday'),
(71, 'Aulas Pietra e Lucas', '', '2025-12-17', '20:30:00', 20, 22, 1, 'wednesday'),
(72, 'Aulas Fabiane Coelho', 'https://www.youtube.com/watch?v=0x1WRY4fvz4 ; https://www.englishclub.com/pronunciation/minimal-pairs-i-ee.php; http://iteslj.org/questions/jobs.html > foco na pronúncia', '2025-12-02', '08:00:00', 21, 22, 1, 'tuesday'),
(73, 'Aulas Fabiane Coelho', 'https://www.youtube.com/watch?v=0x1WRY4fvz4 ; https://www.englishclub.com/pronunciation/minimal-pairs-i-ee.php; http://iteslj.org/questions/jobs.html > foco na pronúncia', '2025-12-09', '08:00:00', 21, 22, 1, 'tuesday'),
(74, 'Aulas Fabiane Coelho', 'Guess who file + conversation http://iteslj.org/questions/goals.html + song', '2025-12-16', '08:00:00', 21, 22, 1, 'tuesday'),
(76, 'Aulas Fabiane Coelho', 'Adverbs: https://test-english.com/grammar-points/b1/comparative-superlative-adjectives-adverbs/; https://mrmrsenglish.com/wp-content/uploads/2025/07/Common-English-Sentences-Using-Adverbs-of-Comparison-scaled.png', '2025-11-27', '08:00:00', 21, 22, 1, 'thursday'),
(77, 'Aulas Fabiane Coelho', 'https://www.youtube.com/watch?v=0x1WRY4fvz4 ; https://www.englishclub.com/pronunciation/minimal-pairs-i-ee.php; http://iteslj.org/questions/jobs.html > foco na pronúncia', '2025-12-04', '08:00:00', 21, 22, 1, 'thursday'),
(78, 'Aulas Fabiane Coelho', 'Pronunciation', '2025-12-11', '08:00:00', 21, 22, 1, 'thursday'),
(79, 'Aulas Fabiane Coelho', '', '2025-12-18', '08:00:00', 21, 22, 1, 'thursday'),
(80, 'Aula Bruna', 'Conversation', '2025-11-27', '11:30:00', 13, 22, 0, NULL),
(81, 'Aulas Bruna Muraro', '', '2025-12-04', '18:00:00', 27, 22, 1, 'thursday'),
(82, 'Aulas Bruna Muraro', '', '2025-12-11', '18:00:00', 27, 22, 1, 'thursday'),
(83, 'Aulas Bruna Muraro', 'Believer song', '2026-01-15', '18:00:00', 27, 22, 1, 'thursday'),
(86, 'Aulas Bruna Muraro', '', '2025-12-11', '18:00:00', 27, 22, 1, 'thursday'),
(89, 'Bruna Muraro', 'https://test-english.com/vocabulary/a2/say-tell-speak-talk-etc-a2-english-vocabulary/', '2025-11-27', '18:00:00', 27, 22, 0, NULL),
(90, 'Aulas Tathi', 'https://www.newsinlevels.com/products/food-and-art-level-2/', '2025-11-28', '09:40:00', 31, 22, 0, NULL),
(91, 'Aulas Isabela', '', '2025-11-29', '10:15:00', 22, 22, 0, NULL),
(92, 'Aulas Giovanna e Murilo', '', '2025-11-29', '09:15:00', 19, 22, 0, NULL),
(93, 'Aulas Alice Guilhoto', '', '2025-12-01', '07:00:00', 15, 22, 1, 'monday'),
(94, 'Aulas Alice Guilhoto', 'https://test-english.com/vocabulary/b1/free-time-activities-b1-english-vocabulary/ \r\nhttps://test-english.com/vocabulary/a2/hobbies-and-free-time-a2-english-vocabulary/2/', '2025-12-08', '07:00:00', 15, 22, 1, 'monday'),
(95, 'Aulas Alice Guilhoto', 'https://www.letras.mus.br/imagine-dragons/bones/ + http://iteslj.org/questions/neighborhood.html', '2025-12-15', '07:00:00', 15, 22, 1, 'monday'),
(97, 'Aulas Alice Guilhoto', '', '2025-12-03', '07:30:00', 15, 22, 1, 'wednesday'),
(98, 'Aulas Alice Guilhoto', 'https://test-english.com/vocabulary/a2/towns-and-cities-a2-english-vocabulary/ + http://iteslj.org/questions/neighborhood.html + https://test-english.com/vocabulary/a1/in-the-town-a1-english-vocabulary/', '2025-12-10', '07:30:00', 15, 22, 1, 'wednesday'),
(99, 'Aulas Alice Guilhoto', 'Guess who + Bones song', '2025-12-17', '07:30:00', 15, 22, 1, 'wednesday'),
(101, 'Aulas Giovanna e Murilo', 'https://test-english.com/grammar-points/a1/there-is-there-are/', '2025-12-02', '20:30:00', 19, 22, 1, 'monday'),
(102, 'Aulas Giovanna e Murilo', '', '2025-12-08', '19:30:00', 19, 22, 1, 'monday'),
(103, 'Aulas Giovanna e Murilo', '', '2025-12-15', '19:30:00', 19, 22, 1, 'monday'),
(105, 'Aulas Gi e Murilo', '', '2025-12-06', '09:15:00', 19, 22, 0, NULL),
(106, 'Aulas Nicky Bryan', '', '2025-12-01', '14:00:00', 16, 22, 1, 'monday'),
(107, 'Aulas Nicky Bryan', '', '2025-12-08', '14:00:00', 16, 22, 1, 'monday'),
(108, 'Aulas Nicky Bryan', '', '2025-12-15', '14:00:00', 16, 22, 1, 'monday'),
(110, 'Aulas Nicky Bryan', '', '2025-12-03', '14:00:00', 16, 22, 1, 'wednesday'),
(111, 'Aulas Nicky Bryan', '', '2025-12-10', '14:00:00', 16, 22, 1, 'wednesday'),
(112, 'Aulas Nicky Bryan', '', '2025-12-17', '14:00:00', 16, 22, 1, 'wednesday'),
(114, 'Aulas Nicky Bryan', 'Quantifiers; biography; listening', '2025-12-04', '14:00:00', 16, 22, 1, 'thursday'),
(115, 'Aulas Nicky Bryan', '', '2025-12-11', '14:00:00', 16, 22, 1, 'thursday'),
(116, 'Aulas Nicky Bryan', '', '2025-12-18', '14:00:00', 16, 22, 1, 'thursday'),
(118, 'Aulas Pietra e Lucas', 'https://test-english.com/vocabulary/b1-b2/air-travel-b1-b2-english-vocabulary/ ; http://iteslj.org/questions/airplanes.html; https://www.youtube.com/watch?v=0z0E30uOMbs', '2025-12-01', '20:30:00', 20, 22, 0, NULL),
(119, 'Aulas Isabela Rossa', '', '2025-12-02', '13:00:00', 22, 22, 1, 'tuesday'),
(120, 'Aulas Isabela Rossa', '', '2025-12-09', '13:00:00', 22, 22, 1, 'tuesday'),
(121, 'Aulas Isabela Rossa', '', '2025-12-16', '13:00:00', 22, 22, 1, 'tuesday'),
(123, 'Aulas Bruna Carolina', '', '2025-12-02', '15:00:00', 13, 22, 1, 'tuesday'),
(124, 'Aulas Bruna Carolina', '', '2025-12-09', '15:00:00', 13, 22, 1, 'tuesday'),
(125, 'Aulas Bruna Carolina', '', '2025-12-16', '15:00:00', 13, 22, 1, 'tuesday'),
(127, 'Aulas Bruna Carolina', '', '2025-12-03', '16:00:00', 13, 22, 1, 'thursday'),
(128, 'Aulas Bruna Carolina', '', '2025-12-11', '11:30:00', 13, 22, 1, 'thursday'),
(129, 'Aulas Bruna Carolina', '', '2025-12-18', '15:00:00', 13, 22, 1, 'thursday'),
(131, 'Aulas Bruna Carolina', 'Finish listening https://www.youtube.com/watch?v=0x1WRY4fvz4 // present continuous https://test-english.com/grammar-points/a1/present-continuous/3/', '2025-12-05', '15:00:00', 13, 22, 1, 'friday'),
(132, 'Aulas Bruna Carolina', '', '2025-12-12', '15:00:00', 13, 22, 1, 'friday'),
(133, 'Aulas Bruna Carolina', 'Conversation', '2025-12-19', '16:00:00', 13, 22, 1, 'friday'),
(135, 'Aulas Kleber', 'https://www.youtube.com/watch?v=0x1WRY4fvz4 ; https://www.englishclub.com/pronunciation/minimal-pairs-i-ee.php; http://iteslj.org/questions/jobs.html > foco na pronúncia', '2025-12-03', '18:00:00', 18, 22, 0, NULL),
(136, 'Aulas Sônia Oliveira', '', '2025-12-10', '19:30:00', 26, 22, 1, 'wednesday'),
(137, 'Aulas Sônia Oliveira', 'Phrasal verbs + work conversation', '2025-12-17', '19:30:00', 26, 22, 1, 'wednesday'),
(138, 'Aulas Sônia', 'https://www.youtube.com/watch?v=0x1WRY4fvz4 ; https://www.conversationstarters.com/questions-by-topic/work.htm', '2025-12-03', '19:30:00', 26, 22, 0, NULL),
(139, 'Aulas Yasmin', 'Welcome + idioms', '2025-12-04', '07:10:00', 29, 22, 0, NULL),
(140, 'Aulas Bruna Carolina', 'Giving opinions + listening', '2025-12-04', '11:30:00', 13, 22, 0, NULL),
(141, 'Aulas Alice Guilhoto', 'Conversation + hobbies: https://test-english.com/vocabulary/a2/hobbies-and-free-time-a2-english-vocabulary/2/', '2025-12-05', '07:30:00', 15, 22, 0, NULL),
(142, 'Aulas Isabela', '', '2025-12-06', '10:15:00', 22, 22, 0, NULL),
(143, 'Aulas Isabela', '', '2025-12-06', '10:15:00', 22, 22, 0, NULL),
(144, 'Aulas Beatriz e Eliana', '', '2025-12-08', '07:00:00', 14, 22, 1, 'monday'),
(145, 'Aulas Beatriz e Eliana', '', '2025-12-15', '07:00:00', 14, 22, 1, 'monday'),
(146, 'Aulas Kleber', 'https://www.youtube.com/watch?v=0x1WRY4fvz4 ; https://www.englishclub.com/pronunciation/minimal-pairs-i-ee.php; http://iteslj.org/questions/jobs.html > foco na pronúncia', '2025-12-09', '18:30:00', 18, 22, 0, NULL),
(147, 'Aulas Pietra e Lucas', 'https://test-english.com/vocabulary/b1/the-body-parts-and-actions-b1-english-vocabulary/ +', '2025-12-09', '09:00:00', 20, 22, 0, NULL),
(148, 'Aulas Yasmin', 'https://docs.google.com/document/d/1_EHl1T2eWjmlRRzfTUejuApCSnxBx7VgMGlKwhCQmZk/edit?usp=sharing', '2025-12-12', '07:30:00', 29, 22, 0, NULL),
(149, 'Aulas Tathi', 'Pronunciation + welcome', '2025-12-10', '09:10:00', 31, 22, 0, NULL),
(150, 'Aulas Kleber', '', '2025-12-10', '18:00:00', 18, 22, 0, NULL),
(151, 'Aulas Kleber', '', '2025-12-10', '18:00:00', 18, 22, 0, NULL),
(152, 'Aulas Ana e Diego', 'https://www.businessenglishresources.com/expressions-describing-job-company/ + https://www.conversationstarters.com/questions-by-topic/work.htm', '2025-12-12', '08:30:00', 30, 22, 0, NULL),
(153, 'Aulas Daniel e Leandro', '', '2025-12-12', '18:00:00', 28, 22, 0, NULL),
(154, 'Aulas Isabela', '', '2025-12-15', '15:00:00', 22, 22, 0, NULL),
(155, 'Aulas Ana e Diego', 'Business idioms', '2025-12-19', '08:30:00', 30, 22, 0, NULL),
(156, 'Aulas Boss Ladies Club', 'https://www.englishclub.com/pronunciation/minimal-pairs-ch-sh.php // https://open.spotify.com/episode/189x2ZVd39L1TJ7sED4xnp?si=0a3a14f4bac84d60', '2026-02-03', '19:30:00', 24, 22, 1, 'tuesday'),
(157, 'Aulas Boss Ladies Club', 'https://www.youtube.com/watch?v=UvdTpywTmQg', '2026-01-13', '19:30:00', 24, 22, 1, 'tuesday'),
(158, 'Aulas Boss Ladies Club', '', '2026-01-20', '20:00:00', 24, 22, 1, 'tuesday'),
(159, 'Aulas Boss Ladies Club', '', '2026-01-27', '20:00:00', 24, 22, 1, 'tuesday'),
(160, 'Aula experimental - Cícero', 'http://iteslj.org/questions/getting.html // https://test-english.com/vocabulary/a1/food-and-meals-a1-english-vocabulary/ // http://iteslj.org/questions/food.html // https://www.youtube.com/watch?v=wg6S356n5xo', '2026-01-05', '16:00:00', 32, 22, 0, NULL),
(161, 'Aulas Caio Dela Marta', '', '2026-01-05', '10:00:00', 10, 22, 0, NULL),
(162, 'Aulas Caio Dela Marta', '', '2026-01-06', '10:00:00', 10, 22, 0, NULL),
(163, 'Aulas Caio Dela Marta', '', '2026-01-07', '10:00:00', 10, 22, 0, NULL),
(164, 'Aulas Caio Dela Marta', '', '2026-01-08', '11:30:00', 10, 22, 0, NULL),
(165, 'Aulas Caio Dela Marta', '', '2026-01-09', '10:00:00', 10, 22, 0, NULL),
(166, 'Aulas Caio Dela Marta', '', '2026-01-06', '20:00:00', 10, 22, 0, NULL),
(167, 'Aulas Caio Dela Marta', '', '2026-01-05', '20:00:00', 10, 22, 0, NULL),
(168, 'Aulas Caio Dela Marta', '', '2026-01-07', '20:00:00', 10, 22, 0, NULL),
(169, 'Aulas Caio Dela Marta', '', '2026-01-09', '20:00:00', 10, 22, 0, NULL),
(170, 'Aulas Caio Dela Marta', '', '2026-01-08', '20:00:00', 10, 22, 0, NULL),
(171, 'Aulas Isabela Rossa', '', '2026-01-16', '13:00:00', 22, 22, 1, 'tuesday'),
(172, 'Aulas Isabela Rossa', '', '2026-01-20', '14:00:00', 22, 22, 1, 'tuesday'),
(173, 'Aulas Isabela Rossa', '', '2026-01-27', '14:00:00', 22, 22, 1, 'tuesday'),
(174, 'Aulas Isabela Rossa', 'https://test-english.com/vocabulary/b1-b2/air-travel-b1-b2-english-vocabulary/', '2026-02-03', '14:00:00', 22, 22, 1, 'tuesday'),
(175, 'Aulas Isabela Rossa', 'https://test-english.com/vocabulary/b1-b2/air-travel-b1-b2-english-vocabulary/ // http://iteslj.org/questions/travel.html', '2026-02-10', '14:00:00', 22, 22, 1, 'tuesday'),
(176, 'Aulas Isabela Rossa', 'Sem aula: feriado de carnaval', '2026-02-17', '13:00:00', 22, 22, 1, 'tuesday'),
(179, 'Aulas Isabela Rossa', '', '2026-01-31', '10:00:00', 22, 22, 1, 'saturday'),
(180, 'Aulas Isabela Rossa', '', '2026-01-23', '14:00:00', 22, 22, 1, 'saturday'),
(181, 'Aulas Beatriz e Eliana', '', '2026-01-12', '08:00:00', 14, 22, 1, 'monday'),
(182, 'Aulas Beatriz e Eliana', 'https://open.spotify.com/episode/54m7ogsV9JZBREHLA0zFJ5?si=04d1763e6406451f', '2026-01-21', '11:00:00', 14, 22, 1, 'monday'),
(183, 'Aulas Beatriz e Eliana', 'https://open.spotify.com/episode/54m7ogsV9JZBREHLA0zFJ5?si=04d1763e6406451f', '2026-01-28', '11:00:00', 14, 22, 1, 'monday'),
(184, 'Aulas Beatriz e Eliana', 'https://open.spotify.com/episode/54m7ogsV9JZBREHLA0zFJ5?si=04d1763e6406451f', '2026-02-04', '11:00:00', 14, 22, 1, 'monday'),
(185, 'Aulas Beatriz e Eliana', 'https://www.englishclub.com/pronunciation/minimal-pairs-m-n.php //  https://test-english.com/vocabulary/a1/time-words-a1-english-vocabulary/', '2026-02-11', '11:00:00', 14, 22, 1, 'monday'),
(186, 'Aulas Beatriz e Eliana', '', '2026-02-18', '11:00:00', 14, 22, 1, 'monday'),
(187, 'Aulas Beatriz e Eliana', '', '2026-02-23', '08:00:00', 14, 22, 1, 'monday'),
(188, 'Aulas Beatriz e Eliana', '', '2026-03-02', '08:00:00', 14, 22, 1, 'monday'),
(189, 'Aulas Ana e Diego', '', '2026-01-09', '08:30:00', 30, 22, 1, 'friday'),
(190, 'Aulas Ana e Diego', '', '2026-01-16', '08:30:00', 30, 22, 1, 'friday'),
(191, 'Aulas Ana e Diego', 'https://docs.google.com/document/d/1wC7SfKMQH81Zp2IDZ1eOmZ8-lyVyLk6IoYlYNROabcM/edit?tab=t.0', '2026-01-23', '08:30:00', 30, 22, 1, 'friday'),
(192, 'Aulas Ana e Diego', 'https://docs.google.com/document/d/1wC7SfKMQH81Zp2IDZ1eOmZ8-lyVyLk6IoYlYNROabcM/edit?usp=drive_link', '2026-01-30', '08:30:00', 30, 22, 1, 'friday'),
(193, 'Aulas Ana e Diego', 'https://docs.google.com/document/d/1wC7SfKMQH81Zp2IDZ1eOmZ8-lyVyLk6IoYlYNROabcM/edit?usp=drive_link // https://speakspeak.com/resources/general-english-vocabulary/100-essential-business-english-verbs', '2026-02-06', '08:30:00', 30, 22, 1, 'friday'),
(194, 'Aulas Ana e Diego', 'https://docs.google.com/document/d/1wC7SfKMQH81Zp2IDZ1eOmZ8-lyVyLk6IoYlYNROabcM/edit?usp=drive_link // https://www.grammar.cl/rules/pronunciation-of-ed-in-english.jpg', '2026-02-13', '08:30:00', 30, 22, 1, 'friday'),
(195, 'Aulas Ana e Diego', '', '2026-02-20', '08:30:00', 30, 22, 1, 'friday'),
(196, 'Aulas Ana e Diego', '', '2026-02-27', '08:30:00', 30, 22, 1, 'friday'),
(197, 'Aulas Bruna Carolina', '', '2026-01-13', '15:00:00', 13, 22, 1, 'tuesday'),
(198, 'Aulas Bruna Carolina', '', '2026-01-20', '15:00:00', 13, 22, 1, 'tuesday'),
(199, 'Aulas Bruna Carolina', 'http://iteslj.org/questions/lifesofar.html', '2026-02-05', '11:30:00', 13, 22, 1, 'tuesday'),
(200, 'Aulas Bruna Carolina', 'https://www.englishclub.com/pronunciation/minimal-pairs-s-sh.php // https://www.englishclub.com/pronunciation/minimal-pairs-h-r-initial.php // https://www.youtube.com/shorts/OrcukFJrzj0', '2026-02-03', '15:00:00', 13, 22, 1, 'tuesday'),
(201, 'Aulas Bruna Carolina', 'How was your weekend - PDF', '2026-02-10', '15:00:00', 13, 22, 1, 'tuesday'),
(202, 'Aulas Bruna Carolina', '', '2026-02-17', '15:00:00', 13, 22, 1, 'tuesday'),
(203, 'Aulas Bruna Carolina', '', '2026-02-24', '15:00:00', 13, 22, 1, 'tuesday'),
(204, 'Aulas Bruna Carolina', '', '2026-03-03', '15:00:00', 13, 22, 1, 'tuesday'),
(205, 'Aulas Bruna Carolina', '', '2026-03-10', '15:00:00', 13, 22, 1, 'tuesday'),
(206, 'Aulas Bruna Carolina', '', '2026-03-17', '15:00:00', 13, 22, 1, 'tuesday'),
(207, 'Aulas Bruna Carolina', '', '2026-03-24', '15:00:00', 13, 22, 1, 'tuesday'),
(208, 'Aulas Bruna Carolina', '', '2026-03-31', '15:00:00', 13, 22, 1, 'tuesday'),
(210, 'Aulas Bruna Carolina', '', '2026-01-15', '15:00:00', 13, 22, 1, 'thursday'),
(211, 'Aulas Bruna Carolina', 'https://test-english.com/grammar-points/a1/past-simple-regular-irregular/ / https://test-english.com/grammar-points/a1/past-simple-negatives-questions/', '2026-01-22', '15:00:00', 13, 22, 1, 'thursday'),
(212, 'Aulas Bruna Carolina', 'https://test-english.com/listening/a1/what-did-you-do-last-weekend-a1-english-listening-test/', '2026-01-29', '15:00:00', 13, 22, 1, 'thursday'),
(213, 'Aulas Bruna Carolina', 'https://youtube.com/shorts/Ll800tn2_oE?si=8Parama7fs_U6JEI // https://test-english.com/vocabulary/a2/words-with-prepositions-a2-english-vocabulary/', '2026-02-05', '15:00:00', 13, 22, 1, 'thursday'),
(214, 'Aulas Bruna Carolina', 'https://test-english.com/grammar-points/a2/something-anything-nothing-etc/', '2026-02-12', '15:00:00', 13, 22, 1, 'thursday'),
(215, 'Aulas Bruna Carolina', '', '2026-02-19', '15:00:00', 13, 22, 1, 'thursday'),
(216, 'Aulas Bruna Carolina', '', '2026-02-26', '15:00:00', 13, 22, 1, 'thursday'),
(217, 'Aulas Bruna Carolina', '', '2026-03-05', '15:00:00', 13, 22, 1, 'thursday'),
(218, 'Aulas Bruna Carolina', '', '2026-03-12', '15:00:00', 13, 22, 1, 'thursday'),
(219, 'Aulas Bruna Carolina', '', '2026-03-19', '15:00:00', 13, 22, 1, 'thursday'),
(220, 'Aulas Bruna Carolina', '', '2026-03-26', '15:00:00', 13, 22, 1, 'thursday'),
(222, 'Aulas Bruna Carolina', 'https://youtube.com/shorts/XkTi199gM5Q?si=iqneaK-15wN_ahDI > https://www.youtube.com/watch?v=_exaDJr_31o&list=PL9IZbPEwk5p27PAxKf4WDDwh0-jyg240W', '2026-01-16', '15:00:00', 13, 22, 1, 'friday'),
(223, 'Aulas Bruna Carolina', 'https://test-english.com/grammar-points/a2/past-simple-form-use/ // https://www.newsinlevels.com/products/the-us-attacks-venezuela-level-2/', '2026-01-23', '15:00:00', 13, 22, 1, 'friday'),
(224, 'Aulas Bruna Carolina', 'https://youtube.com/shorts/Ll800tn2_oE?si=8Parama7fs_U6JEI // https://test-english.com/vocabulary/a2/words-with-prepositions-a2-english-vocabulary/', '2026-01-30', '15:00:00', 13, 22, 1, 'friday'),
(225, 'Aulas Bruna Carolina', 'https://test-english.com/grammar-points/a2/something-anything-nothing-etc/', '2026-02-06', '15:00:00', 13, 22, 1, 'friday'),
(226, 'Aulas Bruna Carolina', 'Am I wrong song -', '2026-02-13', '15:00:00', 13, 22, 1, 'friday'),
(227, 'Aulas Bruna Carolina', '', '2026-02-20', '15:00:00', 13, 22, 1, 'friday'),
(228, 'Aulas Bruna Carolina', '', '2026-02-27', '15:00:00', 13, 22, 1, 'friday'),
(229, 'Aulas Bruna Carolina', '', '2026-03-06', '15:00:00', 13, 22, 1, 'friday'),
(230, 'Aulas Bruna Carolina', '', '2026-03-13', '15:00:00', 13, 22, 1, 'friday'),
(231, 'Aulas Bruna Carolina', '', '2026-03-20', '15:00:00', 13, 22, 1, 'friday'),
(232, 'Aulas Bruna Carolina', '', '2026-03-27', '15:00:00', 13, 22, 1, 'friday'),
(233, 'Aulas Nicky Bryan', '', '2026-01-13', '14:00:00', 16, 22, 1, 'monday'),
(234, 'Aulas Nicky Bryan', 'https://www.youtube.com/watch?v=Jb90D9q_Q3k', '2026-01-19', '14:00:00', 16, 22, 1, 'monday'),
(235, 'Aulas Nicky Bryan', 'https://test-english.com/grammar-points/a1/past-simple-regular-irregular/ / https://test-english.com/grammar-points/a2/past-simple-form-use/', '2026-01-26', '14:00:00', 16, 22, 1, 'monday'),
(236, 'Aulas Nicky Bryan', 'https://www.englishclub.com/pronunciation/minimal-pairs-s-sh.php // https://www.englishclub.com/pronunciation/minimal-pairs-h-r-initial.php', '2026-02-02', '14:00:00', 16, 22, 1, 'monday'),
(237, 'Aulas Nicky Bryan', 'https://www.youtube.com/shorts/XkTi199gM5Q?si=iqneaK-15wN_ahDI', '2026-02-09', '14:00:00', 16, 22, 1, 'monday'),
(239, 'Aulas Nicky Bryan', '', '2026-02-23', '14:00:00', 16, 22, 1, 'monday'),
(240, 'Aulas Nicky Bryan', '', '2026-03-02', '14:00:00', 16, 22, 1, 'monday'),
(241, 'Aulas Nicky Bryan', '', '2026-03-09', '14:00:00', 16, 22, 1, 'monday'),
(242, 'Aulas Nicky Bryan', '', '2026-03-16', '14:00:00', 16, 22, 1, 'monday'),
(243, 'Aulas Nicky Bryan', '', '2026-03-23', '14:00:00', 16, 22, 1, 'monday'),
(244, 'Aulas Nicky Bryan', '', '2026-03-30', '14:00:00', 16, 22, 1, 'monday'),
(245, 'Aulas Nicky Bryan', '', '2026-01-14', '14:00:00', 16, 22, 1, 'wednesday'),
(246, 'Aulas Nicky Bryan', 'https://www.newsinlevels.com/products/the-best-movies-of-2025-level-1/', '2026-01-21', '14:00:00', 16, 22, 1, 'wednesday'),
(247, 'Aulas Nicky Bryan', 'https://test-english.com/grammar-points/a1/past-simple-regular-irregular/ / https://test-english.com/grammar-points/a2/past-simple-form-use/', '2026-01-28', '14:00:00', 16, 22, 1, 'wednesday'),
(248, 'Aulas Nicky Bryan', 'https://www.newsinlevels.com/products/the-us-attacks-venezuela-level-3/ // https://www.englishclub.com/pronunciation/minimal-pairs-s-sh.php // https://www.englishclub.com/pronunciation/minimal-pairs-h-r-initial.php', '2026-02-04', '14:00:00', 16, 22, 1, 'wednesday'),
(249, 'Aulas Nicky Bryan', 'https://www.youtube.com/shorts/XkTi199gM5Q?si=iqneaK-15wN_ahDI', '2026-02-11', '14:00:00', 16, 22, 1, 'wednesday'),
(251, 'Aulas Nicky Bryan', '', '2026-02-25', '14:00:00', 16, 22, 1, 'wednesday'),
(252, 'Aulas Nicky Bryan', '', '2026-03-04', '14:00:00', 16, 22, 1, 'wednesday'),
(253, 'Aulas Nicky Bryan', '', '2026-03-11', '14:00:00', 16, 22, 1, 'wednesday'),
(254, 'Aulas Nicky Bryan', '', '2026-03-18', '14:00:00', 16, 22, 1, 'wednesday'),
(255, 'Aulas Nicky Bryan', '', '2026-03-25', '14:00:00', 16, 22, 1, 'wednesday'),
(256, 'Aulas Nicky Bryan', '', '2026-04-01', '14:00:00', 16, 22, 1, 'wednesday'),
(258, 'Aulas Nicky Bryan', 'https://www.youtube.com/watch?v=Jb90D9q_Q3k', '2026-01-15', '14:00:00', 16, 22, 1, 'thursday'),
(259, 'Aulas Nicky Bryan', '', '2026-01-23', '15:00:00', 16, 22, 1, 'thursday'),
(260, 'Aulas Nicky Bryan', 'https://www.newsinlevels.com/products/the-best-movies-of-2025-level-1/ / https://www.youtube.com/shorts/XkTi199gM5Q?si=iqneaK-15wN_ahDI // https://test-english.com/grammar-points/a1/past-simple-regular-irregular/', '2026-01-29', '14:00:00', 16, 22, 1, 'thursday'),
(261, 'Aulas Nicky Bryan', 'https://www.newsinlevels.com/products/the-us-attacks-venezuela-level-3/ // https://www.youtube.com/shorts/XkTi199gM5Q?si=iqneaK-15wN_ahDI', '2026-02-05', '14:00:00', 16, 22, 1, 'thursday'),
(262, 'Aulas Nicky Bryan', 'https://test-english.com/grammar-points/a1/present-continuous/ // https://1eso1617.wordpress.com/wp-content/uploads/2017/02/a-scene.jpg?w=584', '2026-02-12', '14:00:00', 16, 22, 1, 'thursday'),
(263, 'Aulas Nicky Bryan', '', '2026-02-19', '14:00:00', 16, 22, 1, 'thursday'),
(264, 'Aulas Nicky Bryan', '', '2026-02-26', '14:00:00', 16, 22, 1, 'thursday'),
(265, 'Aulas Nicky Bryan', '', '2026-03-05', '14:00:00', 16, 22, 1, 'thursday'),
(266, 'Aulas Nicky Bryan', '', '2026-03-12', '14:00:00', 16, 22, 1, 'thursday'),
(267, 'Aulas Nicky Bryan', '', '2026-03-19', '14:00:00', 16, 22, 1, 'thursday'),
(268, 'Aulas Nicky Bryan', '', '2026-03-26', '14:00:00', 16, 22, 1, 'thursday'),
(269, 'Aulas Giovanna e Murilo', '', '2026-01-22', '20:30:00', 19, 22, 1, 'thursday'),
(270, 'Aulas Giovanna e Murilo', '', '2026-01-15', '20:30:00', 19, 22, 1, 'thursday'),
(271, 'Aulas Giovanna e Murilo', 'https://www.englishclub.com/pronunciation/-ed.php // https://www.newsinlevels.com/products/the-us-attacks-venezuela-level-3/ // https://www.grammar.cl/rules/pronunciation-of-ed-in-english.jpg', '2026-02-23', '19:30:00', 19, 22, 1, 'monday'),
(272, 'Aulas Giovanna e Murilo', 'https://www.englishclub.com/pronunciation/minimal-pairs-kw-k.php', '2026-02-09', '19:30:00', 19, 22, 1, 'monday'),
(273, 'Aulas Giovanna e Murilo', 'https://open.spotify.com/episode/6eJaq49gBEptAn3nnokrYX?si=69052a5dfa6c4da3', '2026-01-26', '19:30:00', 19, 22, 1, 'monday'),
(274, 'Aulas Giovanna e Murilo', 'https://www.englishclub.com/pronunciation/minimal-pairs-h-r-initial.php // https://www.newsinlevels.com/products/the-us-attacks-venezuela-level-3/', '2026-02-02', '19:30:00', 19, 22, 1, 'monday'),
(276, 'Aulas Daniel e Leandro', '', '2026-01-15', '19:00:00', 28, 22, 1, 'thursday'),
(277, 'Aulas Daniel e Leandro', 'Agrobusiness file offline', '2026-01-22', '19:00:00', 28, 22, 1, 'thursday'),
(278, 'Aulas Daniel e Leandro', 'https://test-english.com/vocabulary/a1/opposite-adjectives-for-describing-people-and-things-a1-english-vocabulary/  homework: https://test-english.com/vocabulary/a1/opposite-adjectives-for-describing-people-and-things-a1-english-vocabulary/', '2026-02-03', '18:00:00', 28, 22, 1, 'thursday'),
(279, 'Aulas Daniel e Leandro', '', '2026-02-10', '20:00:00', 28, 22, 1, 'thursday'),
(280, 'Aulas Daniel e Leandro', 'https://test-english.com/vocabulary/a2/the-countryside-a2-english-vocabulary/2/ // https://www.youtube.com/watch?v=1vrEljMfXYo', '2026-02-12', '19:00:00', 28, 22, 1, 'thursday'),
(281, 'Aulas Daniel e Leandro', '', '2026-02-19', '19:30:00', 28, 22, 1, 'thursday'),
(282, 'Aulas Daniel e Leandro', '', '2026-02-26', '19:30:00', 28, 22, 1, 'thursday'),
(283, 'Aulas Daniel e Leandro', '', '2026-03-05', '19:30:00', 28, 22, 1, 'thursday'),
(284, 'Aulas Daniel e Leandro', '', '2026-03-12', '19:30:00', 28, 22, 1, 'thursday'),
(285, 'Aulas Daniel e Leandro', '', '2026-03-19', '19:30:00', 28, 22, 1, 'thursday'),
(286, 'Aulas Daniel e Leandro', '', '2026-03-26', '19:30:00', 28, 22, 1, 'thursday'),
(287, 'Aulas Fabiane Coelho', '', '2026-01-13', '08:10:00', 21, 22, 1, 'tuesday'),
(288, 'Aulas Fabiane Coelho', 'https://open.spotify.com/episode/54m7ogsV9JZBREHLA0zFJ5?si=04d1763e6406451f', '2026-01-20', '08:10:00', 21, 22, 1, 'tuesday'),
(289, 'Aulas Fabiane Coelho', 'https://open.spotify.com/episode/189x2ZVd39L1TJ7sED4xnp?si=c4335abe04db4476 // https://languagetool.org/insights/post/how-to-ask-for-help-professionally/', '2026-01-27', '08:10:00', 21, 22, 1, 'tuesday'),
(290, 'Aulas Fabiane Coelho', 'https://www.englishclub.com/pronunciation/minimal-pairs-a-u.php // https://docs.google.com/document/d/1zEwr8zs1mEG7UBl_YPtTUxqkTqKiqBNszROQ5ME9iCA/edit?usp=drive_link', '2026-02-02', '09:10:00', 21, 22, 1, 'tuesday'),
(291, 'Aulas Fabiane Coelho', 'http://iteslj.org/questions/lifesofar.html //  https://www.grammar.cl/rules/pronunciation-of-ed-in-english.jpg // https://www.englishclub.com/pronunciation/-ed.php //', '2026-02-10', '08:10:00', 21, 22, 1, 'tuesday'),
(292, 'Aulas Fabiane Coelho', '', '2026-02-17', '08:10:00', 21, 22, 1, 'tuesday'),
(293, 'Aulas Fabiane Coelho', '', '2026-02-24', '08:10:00', 21, 22, 1, 'tuesday'),
(294, 'Aulas Fabiane Coelho', '', '2026-03-03', '08:10:00', 21, 22, 1, 'tuesday'),
(296, 'Aulas Fabiane Coelho', '', '2026-01-15', '08:10:00', 21, 22, 1, 'thursday'),
(297, 'Aulas Fabiane Coelho', 'https://www.newsinlevels.com/products/the-best-movies-of-2025-level-2/ ; https://open.spotify.com/episode/54m7ogsV9JZBREHLA0zFJ5?si=04d1763e6406451f', '2026-01-22', '08:10:00', 21, 22, 1, 'thursday'),
(298, 'Aulas Fabiane Coelho', 'https://open.spotify.com/episode/189x2ZVd39L1TJ7sED4xnp?si=c4335abe04db4476 // https://languagetool.org/insights/post/how-to-ask-for-help-professionally/ how to write an email to it department for help', '2026-01-29', '08:10:00', 21, 22, 1, 'thursday'),
(299, 'Aulas Fabiane Coelho', 'https://docs.google.com/document/d/1zEwr8zs1mEG7UBl_YPtTUxqkTqKiqBNszROQ5ME9iCA/edit?usp=drive_link // https://www.englishclub.com/pronunciation/minimal-pairs-e-ei.php', '2026-02-05', '08:10:00', 21, 22, 1, 'thursday'),
(300, 'Aulas Fabiane Coelho', 'http://iteslj.org/questions/travel.html // https://www.grammar.cl/rules/pronunciation-of-ed-in-english.jpg // https://open.spotify.com/episode/7kJs8rLXkwatxoNtjZcUur?si=286c5c703de74b6b', '2026-02-12', '08:10:00', 21, 22, 1, 'thursday'),
(301, 'Aulas Fabiane Coelho', '', '2026-02-19', '08:10:00', 21, 22, 1, 'thursday'),
(302, 'Aulas Fabiane Coelho', '', '2026-02-26', '08:10:00', 21, 22, 1, 'thursday'),
(303, 'Aulas Alice Guilhoto', '', '2026-01-12', '07:30:00', 15, 22, 1, 'monday'),
(304, 'Aulas Alice Guilhoto', '', '2026-01-19', '08:00:00', 15, 22, 1, 'monday'),
(305, 'Aulas Alice Guilhoto', 'https://open.spotify.com/episode/54m7ogsV9JZBREHLA0zFJ5?si=04d1763e6406451f // https://test-english.com/vocabulary/a2/common-phrasal-verbs-a2-english-vocabulary/', '2026-01-26', '07:30:00', 15, 22, 1, 'monday'),
(306, 'Aulas Alice Guilhoto', 'https://test-english.com/grammar-points/a2/present-perfect/', '2026-02-02', '08:00:00', 15, 22, 1, 'monday'),
(307, 'Aulas Alice Guilhoto', 'https://open.spotify.com/episode/54m7ogsV9JZBREHLA0zFJ5?si=04d1763e6406451f // https://test-english.com/vocabulary/a2/common-phrasal-verbs-a2-english-vocabulary/', '2026-01-14', '07:30:00', 15, 22, 1, 'wednesday'),
(308, 'Aulas Alice Guilhoto', 'https://open.spotify.com/episode/54m7ogsV9JZBREHLA0zFJ5?si=04d1763e6406451f', '2026-01-21', '08:00:00', 15, 22, 1, 'wednesday'),
(309, 'Aulas Alice Guilhoto', 'https://open.spotify.com/episode/54m7ogsV9JZBREHLA0zFJ5?si=04d1763e6406451f // https://test-english.com/vocabulary/a2/common-phrasal-verbs-a2-english-vocabulary/', '2026-01-28', '07:30:00', 15, 22, 1, 'wednesday'),
(310, 'Aulas Alice Guilhoto', 'https://test-english.com/grammar-points/a2/present-perfect/ // https://www.englishclub.com/pronunciation/minimal-pairs-ch-sh.php // http://iteslj.org/questions/haveyou.html', '2026-02-04', '08:00:00', 15, 22, 1, 'wednesday'),
(311, 'Aulas Jorge Pontes', 'https://www.youtube.com/watch?v=UvdTpywTmQg > Bucket list > https://med.stanford.edu/letter/bucket-list/what-is-bucket-list.html', '2026-01-15', '11:30:00', 11, 22, 1, 'tuesday'),
(312, 'Aulas Jorge Pontes', 'https://youtube.com/shorts/LqPshIepysE?si=r1vB48E82sIstSbo ; https://docs.google.com/document/d/1UuK0Qxz_Amu6nVne202ttvT0IV3qYsa4cXe_WZ1YjHg/edit?usp=sharing', '2026-01-20', '11:30:00', 11, 22, 1, 'tuesday'),
(313, 'Aulas Jorge Pontes', 'https://docs.google.com/document/d/1qwl5Zw40Gi7IN7sBN0D745PLJw8QV9ReBo3KndLJblw/edit?usp=sharing // https://www.youtube.com/watch?v=f_N3PGvnVKg', '2026-01-29', '11:30:00', 11, 22, 1, 'tuesday'),
(314, 'Aulas Jorge Pontes', 'https://docs.google.com/document/d/1qwl5Zw40Gi7IN7sBN0D745PLJw8QV9ReBo3KndLJblw/edit?usp=drive_link //', '2026-02-12', '10:00:00', 11, 22, 1, 'tuesday'),
(315, 'Aulas Jorge Pontes', 'https://docs.google.com/document/d/1gJi4SZHAPWcT7eAM_Vi46MjieZ257azLt1-REh1X0Rk/edit?usp=sharing // https://test-english.com/vocabulary/b1-b2/word-pairs-b1-english-vocabulary/2/', '2026-02-10', '11:30:00', 11, 22, 1, 'tuesday'),
(319, 'Aulas Pietra e Lucas', 'https://www.espressoenglish.net/100-idioms-meanings-examples/', '2026-01-19', '20:30:00', 20, 22, 1, 'monday'),
(320, 'Aulas Pietra e Lucas', '', '2026-01-26', '20:30:00', 20, 22, 1, 'monday'),
(321, 'Aulas Pietra e Lucas', 'https://www.englishclub.com/pronunciation/minimal-pairs-s-sh.php // https://www.englishclub.com/pronunciation/minimal-pairs-h-r-initial.php // homework: https://www.newsinlevels.com/products/the-us-attacks-venezuela-level-3/', '2026-02-02', '20:30:00', 20, 22, 1, 'monday'),
(322, 'Aulas Pietra e Lucas', 'https://www.grammar.cl/rules/pronunciation-of-ed-in-english.jpg // https://www.englishclub.com/pronunciation/-ed.php // http://iteslj.org/questions/lifesofar.html', '2026-02-09', '20:30:00', 20, 22, 1, 'monday'),
(323, 'Aulas Pietra e Lucas', '', '2026-02-16', '20:30:00', 20, 22, 1, 'monday'),
(324, 'Aulas Pietra e Lucas', '', '2026-02-23', '20:30:00', 20, 22, 1, 'monday'),
(325, 'Aulas Pietra e Lucas', '', '2026-03-02', '20:30:00', 20, 22, 1, 'monday'),
(326, 'Aulas Pietra e Lucas', '', '2026-03-09', '20:30:00', 20, 22, 1, 'monday'),
(327, 'Aulas Pietra e Lucas', '', '2026-03-16', '20:30:00', 20, 22, 1, 'monday'),
(328, 'Aulas Pietra e Lucas', '', '2026-03-23', '20:30:00', 20, 22, 1, 'monday'),
(329, 'Aulas Pietra e Lucas', '', '2026-03-30', '20:30:00', 20, 22, 1, 'monday'),
(330, 'Aulas Pietra e Lucas', '', '2026-04-06', '20:30:00', 20, 22, 1, 'monday'),
(331, '1st class', 'sixth\r\nrecover myself\r\nhip\r\ngoing easily\r\nday-use\r\n\r\nYou 20:44\r\nmeet/ know\r\nin a row\r\ndiet\r\n\r\nYou 20:49\r\nbulking diet\r\nCutting Diet\r\n\r\nYou 21:07\r\nstraight\r\ngained\r\nearned\r\nkeychain\r\npipette\r\n\r\ncar repair', '2026-01-12', '20:30:00', 20, 22, 0, NULL),
(332, 'Aulas Sônia Oliveira', 'https://www.youtube.com/watch?v=UvdTpywTmQg > Bucket list > https://med.stanford.edu/letter/bucket-list/what-is-bucket-list.html', '2026-01-14', '19:30:00', 26, 22, 1, 'wednesday'),
(333, 'Aulas Sônia Oliveira', 'https://docs.google.com/document/d/1wC7SfKMQH81Zp2IDZ1eOmZ8-lyVyLk6IoYlYNROabcM/edit?tab=t.0 / https://www.englishradar.com/english-vocabulary/business-idioms/', '2026-01-21', '19:30:00', 26, 22, 1, 'wednesday'),
(334, 'Aulas Sônia Oliveira', 'https://docs.google.com/document/d/1qwl5Zw40Gi7IN7sBN0D745PLJw8QV9ReBo3KndLJblw/edit?usp=drive_link', '2026-01-28', '19:30:00', 26, 22, 1, 'wednesday'),
(335, 'Aulas Sônia Oliveira', 'https://docs.google.com/document/d/1wC7SfKMQH81Zp2IDZ1eOmZ8-lyVyLk6IoYlYNROabcM/edit?usp=sharing', '2026-02-04', '19:30:00', 26, 22, 1, 'wednesday'),
(336, 'Aulas Sônia Oliveira', 'https://docs.google.com/document/d/1wC7SfKMQH81Zp2IDZ1eOmZ8-lyVyLk6IoYlYNROabcM/edit?usp=drive_link', '2026-02-11', '19:30:00', 26, 22, 1, 'wednesday'),
(337, 'Aulas Sônia Oliveira', 'https://test-english.com/grammar-points/b1/compound-adjectives-with-numbers-a-two-day-trip/', '2026-02-18', '19:30:00', 26, 22, 1, 'wednesday'),
(338, 'Aulas Sônia Oliveira', '', '2026-02-25', '19:30:00', 26, 22, 1, 'wednesday'),
(339, 'Aulas Sônia Oliveira', '', '2026-03-04', '19:30:00', 26, 22, 1, 'wednesday'),
(340, 'Aulas Pietra e Lucas', 'https://www.youtube.com/watch?v=UvdTpywTmQg > Bucket list > https://med.stanford.edu/letter/bucket-list/what-is-bucket-list.html', '2026-01-14', '20:30:00', 20, 22, 1, 'wednesday'),
(341, 'Aulas Pietra e Lucas', '', '2026-01-21', '20:30:00', 20, 22, 1, 'wednesday'),
(342, 'Aulas Pietra e Lucas', '', '2026-01-28', '20:30:00', 20, 22, 1, 'wednesday'),
(343, 'Aulas Pietra e Lucas', 'https://www.englishclub.com/pronunciation/phonemic-chart-ia.php // https://www.englishclub.com/pronunciation/minimal-pairs-ch-sh.php // https://www.newsinlevels.com/products/the-us-attacks-venezuela-level-3/', '2026-02-04', '20:30:00', 20, 22, 1, 'wednesday'),
(344, 'Aulas Pietra e Lucas', 'https://docs.google.com/document/d/1zEwr8zs1mEG7UBl_YPtTUxqkTqKiqBNszROQ5ME9iCA/edit?usp=drive_link', '2026-02-13', '07:30:00', 20, 22, 1, 'wednesday'),
(345, 'Aulas Pietra e Lucas', '', '2026-02-18', '20:30:00', 20, 22, 1, 'wednesday'),
(346, 'Aulas Pietra e Lucas', '', '2026-02-25', '20:30:00', 20, 22, 1, 'wednesday'),
(347, 'Aulas Pietra e Lucas', '', '2026-03-04', '20:30:00', 20, 22, 1, 'wednesday'),
(348, 'Aulas Pietra e Lucas', '', '2026-03-11', '20:30:00', 20, 22, 1, 'wednesday'),
(349, 'Aulas Pietra e Lucas', '', '2026-03-18', '20:30:00', 20, 22, 1, 'wednesday'),
(350, 'Aulas Pietra e Lucas', '', '2026-03-25', '20:30:00', 20, 22, 1, 'wednesday'),
(351, 'Aulas Pietra e Lucas', '', '2026-04-01', '20:30:00', 20, 22, 1, 'wednesday'),
(352, 'Aulas Tathiane Saraiva', '', '2026-01-16', '09:40:00', 31, 22, 1, 'friday'),
(353, 'Aulas Tathiane Saraiva', 'https://test-english.com/grammar-points/a2/past-simple-form-use/ // https://www.newsinlevels.com/products/the-us-attacks-venezuela-level-2/', '2026-01-23', '09:40:00', 31, 22, 1, 'friday'),
(354, 'Aulas Tathiane Saraiva', 'https://www.youtube.com/watch?v=x6G6ZlzgrLQ', '2026-01-30', '09:40:00', 31, 22, 1, 'friday'),
(355, 'Aulas Tathiane Saraiva', 'https://www.youtube.com/watch?v=x6G6ZlzgrLQ', '2026-02-06', '09:40:00', 31, 22, 1, 'friday'),
(356, 'Aulas Tathiane Saraiva', '', '2026-02-13', '09:40:00', 31, 22, 1, 'friday'),
(357, 'Aulas Tathiane Saraiva', '', '2026-02-20', '09:40:00', 31, 22, 1, 'friday'),
(358, 'Aulas Tathiane Saraiva', '', '2026-02-27', '09:40:00', 31, 22, 1, 'friday'),
(359, 'Aulas Tathiane Saraiva', '', '2026-03-06', '09:40:00', 31, 22, 1, 'friday'),
(360, 'Aulas Yasmin', '', '2026-01-14', '18:00:00', 29, 22, 0, NULL),
(361, 'Aulas Caio Dela Marta', '', '2026-01-12', '16:00:00', 10, 22, 1, 'monday'),
(362, 'Aulas Caio Dela Marta', 'https://test-english.com/grammar-points/a1/past-simple-regular-irregular/ // https://www.newsinlevels.com/products/the-us-attacks-venezuela-level-3/', '2026-01-26', '16:00:00', 10, 22, 1, 'monday'),
(363, 'Aulas Caio Dela Marta', '', '2026-02-02', '16:00:00', 10, 22, 1, 'monday'),
(364, 'Aulas Caio Dela Marta', 'https://docs.google.com/document/d/1wC7SfKMQH81Zp2IDZ1eOmZ8-lyVyLk6IoYlYNROabcM/edit?tab=t.0', '2026-02-09', '16:00:00', 10, 22, 1, 'monday'),
(365, 'Aulas Caio Dela Marta', '', '2026-02-16', '16:00:00', 10, 22, 1, 'monday'),
(366, 'Aulas Caio Dela Marta', '', '2026-02-23', '16:00:00', 10, 22, 1, 'monday'),
(367, 'Aulas Caio Dela Marta', '', '2026-03-02', '16:00:00', 10, 22, 1, 'monday'),
(368, 'Aulas Caio Dela Marta', '', '2026-03-09', '16:00:00', 10, 22, 1, 'monday'),
(369, 'Aulas Caio Dela Marta', '', '2026-03-16', '16:00:00', 10, 22, 1, 'monday'),
(370, 'Aulas Caio Dela Marta', '', '2026-03-23', '16:00:00', 10, 22, 1, 'monday'),
(371, 'Aulas Caio Dela Marta', '', '2026-03-30', '16:00:00', 10, 22, 1, 'monday'),
(372, 'Aulas Caio Dela Marta', '', '2026-04-06', '16:00:00', 10, 22, 1, 'monday'),
(373, 'Aulas Caio Dela Marta', '', '2026-01-13', '16:00:00', 10, 22, 1, 'tuesday'),
(374, 'Aulas Caio Dela Marta', 'https://test-english.com/grammar-points/a1/past-simple-regular-irregular/ // https://www.newsinlevels.com/products/the-us-attacks-venezuela-level-3/', '2026-01-27', '16:00:00', 10, 22, 1, 'tuesday'),
(375, 'Aulas Caio Dela Marta', '', '2026-02-03', '16:00:00', 10, 22, 1, 'tuesday'),
(376, 'Aulas Caio Dela Marta', '', '2026-02-10', '17:00:00', 10, 22, 1, 'tuesday'),
(377, 'Aulas Caio Dela Marta', '', '2026-02-17', '16:00:00', 10, 22, 1, 'tuesday'),
(378, 'Aulas Caio Dela Marta', '', '2026-02-24', '16:00:00', 10, 22, 1, 'tuesday'),
(379, 'Aulas Caio Dela Marta', '', '2026-03-03', '16:00:00', 10, 22, 1, 'tuesday'),
(380, 'Aulas Caio Dela Marta', '', '2026-03-10', '16:00:00', 10, 22, 1, 'tuesday'),
(381, 'Aulas Caio Dela Marta', '', '2026-03-17', '16:00:00', 10, 22, 1, 'tuesday'),
(382, 'Aulas Caio Dela Marta', '', '2026-03-24', '16:00:00', 10, 22, 1, 'tuesday'),
(383, 'Aulas Caio Dela Marta', '', '2026-03-31', '16:00:00', 10, 22, 1, 'tuesday'),
(384, 'Aulas Caio Dela Marta', '', '2026-04-07', '16:00:00', 10, 22, 1, 'tuesday'),
(385, 'Aulas Caio Dela Marta', '', '2026-01-14', '16:00:00', 10, 22, 1, 'wednesday'),
(386, 'Aulas Caio Dela Marta', 'Business idioms', '2026-01-28', '15:00:00', 10, 22, 1, 'wednesday'),
(387, 'Aulas Caio Dela Marta', '', '2026-02-04', '16:00:00', 10, 22, 1, 'wednesday'),
(388, 'Aulas Caio Dela Marta', '', '2026-02-11', '16:00:00', 10, 22, 1, 'wednesday'),
(389, 'Aulas Caio Dela Marta', '', '2026-02-18', '16:00:00', 10, 22, 1, 'wednesday'),
(390, 'Aulas Caio Dela Marta', '', '2026-02-25', '16:00:00', 10, 22, 1, 'wednesday'),
(391, 'Aulas Caio Dela Marta', '', '2026-03-04', '16:00:00', 10, 22, 1, 'wednesday'),
(392, 'Aulas Caio Dela Marta', '', '2026-03-11', '16:00:00', 10, 22, 1, 'wednesday'),
(393, 'Aulas Caio Dela Marta', '', '2026-03-18', '16:00:00', 10, 22, 1, 'wednesday'),
(394, 'Aulas Caio Dela Marta', '', '2026-03-25', '16:00:00', 10, 22, 1, 'wednesday'),
(395, 'Aulas Caio Dela Marta', '', '2026-04-01', '16:00:00', 10, 22, 1, 'wednesday'),
(396, 'Aulas Caio Dela Marta', '', '2026-04-08', '16:00:00', 10, 22, 1, 'wednesday'),
(397, 'Aulas Caio Dela Marta', 'https://test-english.com/grammar-points/a2/past-simple-form-use/', '2026-01-22', '16:00:00', 10, 22, 1, 'thursday'),
(398, 'Aulas Caio Dela Marta', '', '2026-01-29', '16:00:00', 10, 22, 1, 'thursday'),
(399, 'Aulas Caio Dela Marta', '', '2026-02-05', '16:00:00', 10, 22, 1, 'thursday'),
(400, 'Aulas Caio Dela Marta', '', '2026-02-12', '16:00:00', 10, 22, 1, 'thursday'),
(401, 'Aulas Caio Dela Marta', '', '2026-02-19', '16:00:00', 10, 22, 1, 'thursday'),
(402, 'Aulas Caio Dela Marta', '', '2026-02-26', '16:00:00', 10, 22, 1, 'thursday'),
(403, 'Aulas Caio Dela Marta', '', '2026-03-05', '16:00:00', 10, 22, 1, 'thursday'),
(404, 'Aulas Caio Dela Marta', '', '2026-03-12', '16:00:00', 10, 22, 1, 'thursday'),
(405, 'Aulas Caio Dela Marta', '', '2026-03-19', '16:00:00', 10, 22, 1, 'thursday'),
(406, 'Aulas Caio Dela Marta', '', '2026-03-26', '16:00:00', 10, 22, 1, 'thursday'),
(407, 'Aulas Caio Dela Marta', '', '2026-04-02', '16:00:00', 10, 22, 1, 'thursday'),
(408, 'Aulas Caio Dela Marta', '', '2026-04-09', '16:00:00', 10, 22, 1, 'thursday'),
(409, 'Aulas Caio Dela Marta', '', '2026-01-16', '18:00:00', 10, 22, 1, 'friday'),
(410, 'Aulas Caio Dela Marta', 'https://test-english.com/grammar-points/a2/past-simple-form-use/', '2026-01-23', '16:00:00', 10, 22, 1, 'friday'),
(411, 'Aulas Caio Dela Marta', '', '2026-01-30', '16:00:00', 10, 22, 1, 'friday'),
(412, 'Aulas Caio Dela Marta', '', '2026-02-06', '16:00:00', 10, 22, 1, 'friday'),
(413, 'Aulas Caio Dela Marta', '', '2026-02-13', '16:00:00', 10, 22, 1, 'friday'),
(414, 'Aulas Caio Dela Marta', '', '2026-02-20', '16:00:00', 10, 22, 1, 'friday'),
(415, 'Aulas Caio Dela Marta', '', '2026-02-27', '16:00:00', 10, 22, 1, 'friday'),
(416, 'Aulas Caio Dela Marta', '', '2026-03-06', '16:00:00', 10, 22, 1, 'friday'),
(417, 'Aulas Caio Dela Marta', '', '2026-03-13', '16:00:00', 10, 22, 1, 'friday'),
(418, 'Aulas Caio Dela Marta', '', '2026-03-20', '16:00:00', 10, 22, 1, 'friday'),
(419, 'Aulas Caio Dela Marta', '', '2026-03-27', '16:00:00', 10, 22, 1, 'friday'),
(420, 'Aulas Caio Dela Marta', '', '2026-04-03', '16:00:00', 10, 22, 1, 'friday'),
(421, 'Aulas Caio Dela Marta', '', '2026-01-15', '16:00:00', 10, 22, 0, NULL),
(422, 'Aulas Caio Dela Marta', 'https://www.newsinlevels.com/products/minneapolis-shooting-level-3/', '2026-01-19', '15:00:00', 10, 22, 0, NULL),
(423, 'Aulas Caio Dela Marta', '', '2026-01-20', '16:00:00', 10, 22, 0, NULL),
(424, 'Aulas Caio Dela Marta', '', '2026-01-21', '15:00:00', 10, 22, 0, NULL),
(425, 'Aulas Larissa Eduarda', 'https://test-english.com/vocabulary/a1/common-things-a1-english-vocabulary/', '2026-01-20', '19:00:00', 33, 22, 1, 'friday'),
(426, 'Aulas Larissa Eduarda', '', '2026-01-16', '19:00:00', 33, 22, 1, 'friday'),
(427, 'Aulas Thiago Zattoni', 'https://test-english.com/grammar-points/a1/past-simple-regular-irregular/', '2026-01-26', '13:30:00', 34, 22, 1, 'monday'),
(428, 'Aulas Thiago Zattoni', '', '2026-02-02', '13:30:00', 34, 22, 1, 'monday'),
(429, 'Aulas Thiago Zattoni', 'http://iteslj.org/questions/vacation.html', '2026-02-09', '13:30:00', 34, 22, 1, 'monday'),
(431, 'Aulas Thiago Zattoni', '', '2026-02-23', '13:30:00', 34, 22, 1, 'monday'),
(432, 'Aulas Thiago Zattoni', '', '2026-03-02', '13:30:00', 34, 22, 1, 'monday'),
(433, 'Aulas Thiago Zattoni', '', '2026-03-09', '13:30:00', 34, 22, 1, 'monday'),
(434, 'Aulas Thiago Zattoni', '', '2026-03-16', '13:30:00', 34, 22, 1, 'monday'),
(435, 'Aulas Thiago Zattoni', '', '2026-01-20', '13:30:00', 34, 22, 1, 'tuesday'),
(436, 'Aulas Thiago Zattoni', '', '2026-01-27', '13:30:00', 34, 22, 1, 'tuesday'),
(437, 'Aulas Thiago Zattoni', '', '2026-02-03', '13:30:00', 34, 22, 1, 'tuesday'),
(438, 'Aulas Thiago Zattoni', '', '2026-02-10', '13:30:00', 34, 22, 1, 'tuesday'),
(440, 'Aulas Thiago Zattoni', '', '2026-02-24', '13:30:00', 34, 22, 1, 'tuesday'),
(441, 'Aulas Thiago Zattoni', '', '2026-03-03', '13:30:00', 34, 22, 1, 'tuesday'),
(442, 'Aulas Thiago Zattoni', '', '2026-03-10', '13:30:00', 34, 22, 1, 'tuesday'),
(443, 'Aulas Thiago Zattoni', '', '2026-01-21', '13:30:00', 34, 22, 1, 'wednesday'),
(444, 'Aulas Thiago Zattoni', 'https://test-english.com/grammar-points/a1/past-simple-regular-irregular/', '2026-01-28', '13:30:00', 34, 22, 1, 'wednesday'),
(445, 'Aulas Thiago Zattoni', '', '2026-02-04', '13:30:00', 34, 22, 1, 'wednesday'),
(446, 'Aulas Thiago Zattoni', 'http://iteslj.org/questions/vacation.html', '2026-02-11', '13:30:00', 34, 22, 1, 'wednesday'),
(448, 'Aulas Thiago Zattoni', '', '2026-02-25', '13:30:00', 34, 22, 1, 'wednesday'),
(449, 'Aulas Thiago Zattoni', '', '2026-03-04', '13:30:00', 34, 22, 1, 'wednesday'),
(450, 'Aulas Thiago Zattoni', '', '2026-03-11', '13:30:00', 34, 22, 1, 'wednesday'),
(451, 'Aulas Thiago Zattoni', '', '2026-01-22', '13:30:00', 34, 22, 1, 'thursday'),
(452, 'Aulas Thiago Zattoni', 'https://test-english.com/grammar-points/a1/past-simple-regular-irregular/', '2026-01-29', '13:30:00', 34, 22, 1, 'thursday'),
(453, 'Aulas Thiago Zattoni', '', '2026-02-05', '13:30:00', 34, 22, 1, 'thursday'),
(454, 'Aulas Thiago Zattoni', '', '2026-02-12', '13:30:00', 34, 22, 1, 'thursday'),
(455, 'Aulas Thiago Zattoni', '', '2026-02-19', '13:30:00', 34, 22, 1, 'thursday'),
(456, 'Aulas Thiago Zattoni', '', '2026-02-26', '13:30:00', 34, 22, 1, 'thursday'),
(457, 'Aulas Thiago Zattoni', '', '2026-03-05', '13:30:00', 34, 22, 1, 'thursday'),
(458, 'Aulas Thiago Zattoni', '', '2026-03-12', '13:30:00', 34, 22, 1, 'thursday'),
(459, 'Aulas Thiago Zattoni', 'https://test-english.com/grammar-points/a2/past-simple-form-use/', '2026-01-23', '13:30:00', 34, 22, 1, 'friday'),
(460, 'Aulas Thiago Zattoni', '', '2026-01-30', '13:30:00', 34, 22, 1, 'friday'),
(461, 'Aulas Thiago Zattoni', '', '2026-02-06', '13:30:00', 34, 22, 1, 'friday'),
(462, 'Aulas Thiago Zattoni', 'https://www.newsinlevels.com/products/the-us-attacks-venezuela-level-3/', '2026-02-13', '13:30:00', 34, 22, 1, 'friday'),
(463, 'Aulas Thiago Zattoni', '', '2026-02-20', '13:30:00', 34, 22, 1, 'friday'),
(464, 'Aulas Thiago Zattoni', '', '2026-02-27', '13:30:00', 34, 22, 1, 'friday'),
(465, 'Aulas Thiago Zattoni', '', '2026-03-06', '13:30:00', 34, 22, 1, 'friday'),
(466, 'Aulas Thiago Zattoni', '', '2026-03-13', '13:30:00', 34, 22, 1, 'friday'),
(467, 'Aulas Thiago', '', '2026-01-19', '13:30:00', 34, 22, 0, NULL),
(468, 'Aulas Kleber', 'https://docs.google.com/document/d/1zEwr8zs1mEG7UBl_YPtTUxqkTqKiqBNszROQ5ME9iCA/edit?usp=drive_link', '2026-01-27', '19:00:00', 18, 22, 1, 'monday'),
(469, 'Aulas Kleber', 'https://docs.google.com/document/d/1zEwr8zs1mEG7UBl_YPtTUxqkTqKiqBNszROQ5ME9iCA/edit?usp=drive_link', '2026-02-02', '18:00:00', 18, 22, 1, 'monday'),
(470, 'Aulas Kleber', 'https://docs.google.com/document/d/1zEwr8zs1mEG7UBl_YPtTUxqkTqKiqBNszROQ5ME9iCA/edit?usp=drive_link // https://www.englishclub.com/pronunciation/minimal-pairs-s-sh.php // https://www.englishclub.com/pronunciation/minimal-pairs-kw-k.php', '2026-02-09', '18:00:00', 18, 22, 1, 'monday'),
(471, 'Aulas Kleber ', '', '2026-02-16', '18:00:00', 18, 22, 1, 'monday'),
(472, 'Aulas Kleber', 'https://test-english.com/grammar-points/a2/however-although-time-connectors/ / https://www.englishcurrent.com/speaking/discussion-speech-topics-esl/', '2026-01-21', '18:00:00', 18, 22, 1, 'wednesday'),
(473, 'Aulas Kleber', 'https://docs.google.com/document/d/1zEwr8zs1mEG7UBl_YPtTUxqkTqKiqBNszROQ5ME9iCA/edit?usp=drive_link', '2026-01-28', '17:00:00', 18, 22, 1, 'wednesday'),
(474, 'Aulas Kleber', 'https://docs.google.com/document/d/1zEwr8zs1mEG7UBl_YPtTUxqkTqKiqBNszROQ5ME9iCA/edit?usp=drive_link // https://www.englishclub.com/pronunciation/minimal-pairs-s-sh.php', '2026-02-04', '18:00:00', 18, 22, 1, 'wednesday'),
(475, 'Aulas Kleber', 'https://www.englishclub.com/pronunciation/minimal-pairs-kw-k.php // https://www.englishclub.com/pronunciation/minimal-pairs-m-n.php // https://docs.google.com/document/d/1zEwr8zs1mEG7UBl_YPtTUxqkTqKiqBNszROQ5ME9iCA/edit?usp=drive_link', '2026-02-11', '18:00:00', 18, 22, 1, 'wednesday'),
(476, 'Aulas Kleber', '', '2026-01-19', '18:00:00', 18, 22, 0, NULL),
(477, 'Teste Gabby', '', '2026-01-19', '19:30:00', 32, 22, 0, NULL),
(478, 'Aulas Bruna Muraro', '', '2026-01-22', '18:00:00', 27, 22, 1, 'thursday'),
(479, 'Aulas Bruna Muraro', '', '2026-01-29', '18:00:00', 27, 22, 1, 'thursday');
INSERT INTO `aulas` (`id`, `titulo_aula`, `descricao`, `data_aula`, `horario`, `turma_id`, `professor_id`, `recorrente`, `dia_semana`) VALUES
(480, 'Aulas Bruna Muraro', '', '2026-02-05', '18:00:00', 27, 22, 1, 'thursday'),
(481, 'Aulas Bruna Muraro', '', '2026-02-12', '18:00:00', 27, 22, 1, 'thursday'),
(482, 'Aulas Bruna Muraro', '', '2026-02-19', '18:00:00', 27, 22, 1, 'thursday'),
(483, 'Aulas Bruna Muraro', '', '2026-02-26', '18:00:00', 27, 22, 1, 'thursday'),
(484, 'Aulas Bruna Muraro', '', '2026-03-05', '18:00:00', 27, 22, 1, 'thursday'),
(485, 'Aulas Bruna Muraro', '', '2026-03-12', '18:00:00', 27, 22, 1, 'thursday'),
(486, 'Aulas Bruna Muraro', '', '2026-03-19', '18:00:00', 27, 22, 1, 'thursday'),
(487, 'Aulas Bruna Muraro', '', '2026-03-26', '18:00:00', 27, 22, 1, 'thursday'),
(488, 'Aulas Bruna Muraro', '', '2026-04-02', '18:00:00', 27, 22, 1, 'thursday'),
(489, 'Aulas Bruna Muraro', '', '2026-04-09', '18:00:00', 27, 22, 1, 'thursday'),
(490, 'Aula Rafa', '', '2026-01-23', '11:00:00', 32, 22, 0, NULL),
(491, 'Rafa', '', '2026-01-26', '09:00:00', 32, 22, 0, NULL),
(492, 'Aulas Larissa Eduarda', 'https://test-english.com/vocabulary/a1/opposite-adjectives-for-describing-people-and-things-a1-english-vocabulary/', '2026-01-29', '20:10:00', 33, 22, 1, 'thursday'),
(493, 'Aulas Larissa Eduarda', 'https://test-english.com/vocabulary/a1/opposite-adjectives-for-describing-people-and-things-a1-english-vocabulary/', '2026-02-05', '20:10:00', 33, 22, 1, 'thursday'),
(494, 'Aulas Larissa Eduarda', '', '2026-02-12', '09:00:00', 33, 22, 1, 'thursday'),
(495, 'Aulas Larissa Eduarda', '', '2026-02-19', '20:10:00', 33, 22, 1, 'thursday'),
(496, 'Aulas Larissa Eduarda', '', '2026-02-26', '20:10:00', 33, 22, 1, 'thursday'),
(497, 'Aulas Larissa Eduarda', '', '2026-03-05', '20:10:00', 33, 22, 1, 'thursday'),
(498, 'Aulas Larissa Eduarda', '', '2026-03-12', '20:10:00', 33, 22, 1, 'thursday'),
(499, 'Aulas Larissa Eduarda', '', '2026-03-19', '20:10:00', 33, 22, 1, 'thursday'),
(500, 'Aulas Rafael Gogola', '', '2026-01-30', '11:00:00', 35, 22, 1, 'friday'),
(501, 'Aulas Rafael Gogola', '', '2026-02-06', '11:00:00', 35, 22, 1, 'friday'),
(502, 'Aulas Rafael Gogola', '', '2026-02-09', '09:00:00', 35, 22, 1, 'friday'),
(503, 'Aulas Rafael Gogola', '', '2026-02-20', '11:00:00', 35, 22, 1, 'friday'),
(504, 'Aula Guilherme', '', '2026-01-29', '19:15:00', 32, 22, 0, NULL),
(505, 'Aulas Yasmin Marmo', '', '2026-01-30', '07:30:00', 29, 22, 1, 'friday'),
(506, 'Aulas Yasmin Marmo', '', '2026-02-11', '20:30:00', 29, 22, 1, 'friday'),
(507, 'Aulas Yasmin Marmo', 'https://docs.google.com/document/d/1zEwr8zs1mEG7UBl_YPtTUxqkTqKiqBNszROQ5ME9iCA/edit?usp=drive_link', '2026-02-13', '07:30:00', 29, 22, 1, 'friday'),
(508, 'Aulas Yasmin Marmo', '', '2026-02-20', '07:30:00', 29, 22, 1, 'friday'),
(509, 'Aulas Yasmin Marmo', '', '2026-02-27', '07:30:00', 29, 22, 1, 'friday'),
(510, 'Aulas Yasmin Marmo', '', '2026-03-06', '07:30:00', 29, 22, 1, 'friday'),
(511, 'Aulas Yasmin Marmo', '', '2026-03-13', '07:30:00', 29, 22, 1, 'friday'),
(512, 'Aulas Yasmin Marmo', '', '2026-03-20', '07:30:00', 29, 22, 1, 'friday'),
(513, 'Aulas Yasmin Marmo', '', '2026-03-27', '07:30:00', 29, 22, 1, 'friday'),
(514, 'Aulas Yasmin Marmo', '', '2026-04-03', '07:30:00', 29, 22, 1, 'friday'),
(515, 'Aulas Yasmin Marmo', '', '2026-04-10', '07:30:00', 29, 22, 1, 'friday'),
(516, 'Aulas Yasmin Marmo', '', '2026-04-17', '07:30:00', 29, 22, 1, 'friday'),
(517, 'Aula Rafael', '', '2026-02-03', '11:30:00', 32, 22, 0, NULL),
(518, 'Aulas Gabrielly', '', '2026-01-31', '09:00:00', 36, 22, 1, 'saturday'),
(519, 'Aulas Gabrielly', '', '2026-02-07', '09:00:00', 36, 22, 1, 'saturday'),
(520, 'Aula Mayra', '', '2026-02-05', '21:10:00', 32, 22, 0, NULL),
(521, 'Aula Wallace', '', '2026-02-07', '11:00:00', 32, 22, 0, NULL),
(522, 'Aulas Ana Clara', 'animals vocab https://nationalparksmom.com/grand-canyon-national-park-animals/ // https://youtube.com/shorts/xqgee0GX4Kg?si=8g97_752iivZz69K', '2026-02-13', '19:30:00', 38, 22, 0, NULL),
(523, 'Aulas Alice Guilhoto', 'https://test-english.com/grammar-points/a2/present-perfect/ // https://www.englishclub.com/pronunciation/minimal-pairs-ch-sh.php // http://iteslj.org/questions/haveyou.html', '2026-02-09', '08:00:00', 15, 22, 1, 'monday'),
(524, 'Aulas Alice Guilhoto', '', '2026-02-16', '08:00:00', 15, 22, 1, 'monday'),
(525, 'Aulas Alice Guilhoto', 'https://test-english.com/grammar-points/a2/present-perfect/ // https://www.englishclub.com/pronunciation/minimal-pairs-i-ee.php  // http://iteslj.org/questions/haveyou.html', '2026-02-11', '08:00:00', 15, 22, 1, 'wednesday'),
(526, 'Aulas Alice Guilhoto', '', '2026-02-18', '08:00:00', 15, 22, 1, 'wednesday'),
(527, 'Aulas Alice Guilhoto', '', '2026-02-25', '08:00:00', 15, 22, 1, 'wednesday'),
(528, 'Aulas Alice Guilhoto', 'https://test-english.com/listening/a1/tell-me-about-your-life-a1-english-listening-test/', '2026-02-23', '08:00:00', 15, 22, 1, 'wednesday'),
(529, 'Aulas Guilherme Prado', '', '2026-02-13', '18:30:00', 37, 22, 1, 'tuesday'),
(530, 'Aulas Guilherme Prado', '', '2026-02-17', '18:30:00', 37, 22, 1, 'tuesday'),
(531, 'Aulas Guilherme Prado', '', '2026-02-24', '18:30:00', 37, 22, 1, 'tuesday'),
(532, 'Aulas Guilherme Prado', '', '2026-03-03', '18:30:00', 37, 22, 1, 'tuesday'),
(533, 'Aulas Boss Ladies Club', '', '2026-02-10', '20:00:00', 24, 22, 1, 'tuesday'),
(534, 'Aulas Boss Ladies Club', '', '2026-02-24', '20:00:00', 24, 22, 1, 'tuesday'),
(535, 'Aulas Boss Ladies Club', '', '2026-03-03', '20:00:00', 24, 22, 1, 'tuesday'),
(536, 'Aulas Boss Ladies Club', '', '2026-03-10', '20:00:00', 24, 22, 1, 'tuesday'),
(537, 'Aulas Boss Ladies Club', '', '2026-03-17', '20:00:00', 24, 22, 1, 'tuesday'),
(538, 'Aulas Boss Ladies Club', '', '2026-03-24', '20:00:00', 24, 22, 1, 'tuesday'),
(539, 'Aulas Boss Ladies Club', '', '2026-03-31', '20:00:00', 24, 22, 1, 'tuesday'),
(540, 'Aulas Boss Ladies Club', '', '2026-04-07', '20:00:00', 24, 22, 1, 'tuesday'),
(541, 'Aula Ana', 'https://www.youtube.com/watch?v=X1FAm22hKe0 // https://docs.google.com/document/d/1oUWaBvP7EiOP_eI33HYD3kZqVe7Wtd7URuNTQzdVVQE/edit?usp=drive_link', '2026-02-14', '10:00:00', 38, 22, 0, NULL),
(542, 'Aula Emily', '', '2026-02-13', '17:00:00', 32, 22, 0, NULL),
(544, 'AULA TESTE HORÁRIO', '', '2026-02-16', '12:00:00', 11, 22, 0, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `aulas_conteudos`
--

CREATE TABLE `aulas_conteudos` (
  `id` int(11) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `conteudo_id` int(11) NOT NULL,
  `planejado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `aulas_conteudos`
--

INSERT INTO `aulas_conteudos` (`id`, `aula_id`, `conteudo_id`, `planejado`) VALUES
(132, 34, 65, 1),
(135, 91, 70, 1),
(136, 92, 70, 1),
(137, 93, 70, 1),
(138, 119, 70, 1),
(140, 123, 70, 1),
(141, 50, 70, 1),
(142, 97, 70, 1),
(143, 81, 70, 1),
(144, 127, 70, 1),
(145, 139, 70, 1),
(146, 141, 70, 1),
(147, 144, 70, 1),
(148, 51, 70, 1),
(149, 149, 70, 1),
(150, 153, 70, 1),
(151, 148, 70, 1),
(152, 145, 70, 1),
(153, 133, 70, 1),
(154, 160, 73, 1),
(155, 287, 75, 1),
(156, 171, 75, 1),
(157, 233, 75, 1),
(158, 197, 75, 1),
(159, 157, 75, 1),
(160, 331, 75, 1),
(161, 331, 77, 1),
(162, 311, 75, 1),
(163, 210, 75, 1),
(164, 245, 75, 1),
(165, 307, 75, 1),
(166, 340, 75, 1),
(167, 83, 75, 1),
(168, 276, 75, 1),
(169, 352, 75, 1),
(170, 360, 75, 1),
(171, 360, 77, 1),
(172, 296, 75, 1),
(173, 258, 75, 1),
(174, 276, 84, 1),
(176, 222, 84, 1),
(177, 190, 75, 1),
(178, 426, 84, 1),
(179, 426, 89, 1),
(180, 426, 86, 1),
(181, 426, 88, 1),
(182, 304, 75, 1),
(185, 234, 86, 1),
(186, 304, 84, 1),
(187, 467, 75, 1),
(188, 422, 96, 1),
(189, 422, 88, 1),
(190, 476, 75, 1),
(191, 476, 77, 1),
(192, 319, 86, 1),
(193, 319, 98, 1),
(195, 477, 89, 1),
(196, 477, 86, 1),
(197, 477, 73, 1),
(198, 288, 96, 1),
(199, 246, 86, 1),
(200, 246, 84, 1),
(201, 435, 75, 1),
(203, 172, 96, 1),
(204, 172, 75, 1),
(205, 198, 84, 1),
(207, 198, 86, 1),
(210, 425, 84, 1),
(211, 172, 84, 1),
(212, 198, 75, 1),
(213, 423, 84, 1),
(214, 158, 84, 1),
(215, 158, 86, 1),
(216, 425, 101, 1),
(217, 425, 89, 1),
(219, 158, 77, 1),
(220, 158, 105, 1),
(221, 308, 101, 1),
(222, 308, 84, 1),
(223, 182, 101, 1),
(224, 182, 88, 1),
(225, 182, 84, 1),
(226, 424, 98, 1),
(228, 472, 75, 1),
(229, 341, 98, 1),
(230, 341, 86, 1),
(233, 211, 96, 1),
(234, 269, 84, 1),
(235, 269, 86, 1),
(236, 269, 77, 1),
(238, 478, 75, 1),
(242, 180, 84, 1),
(243, 180, 101, 1),
(244, 180, 86, 1),
(245, 490, 84, 1),
(246, 490, 73, 1),
(247, 490, 86, 1),
(248, 490, 107, 1),
(249, 490, 101, 1),
(250, 490, 89, 1),
(251, 223, 89, 1),
(252, 223, 77, 1),
(254, 305, 88, 1),
(255, 305, 101, 1),
(256, 491, 89, 1),
(257, 491, 84, 1),
(258, 491, 103, 1),
(260, 273, 84, 1),
(263, 273, 101, 1),
(264, 320, 98, 1),
(265, 173, 84, 1),
(266, 173, 86, 1),
(267, 173, 101, 1),
(268, 159, 84, 1),
(269, 159, 89, 1),
(270, 159, 101, 1),
(272, 273, 86, 1),
(275, 183, 84, 1),
(280, 342, 98, 1),
(281, 479, 84, 1),
(282, 479, 75, 1),
(283, 479, 86, 1),
(284, 492, 107, 1),
(285, 492, 101, 1),
(286, 492, 89, 1),
(287, 313, 112, 1),
(288, 504, 89, 1),
(289, 504, 86, 1),
(290, 504, 107, 1),
(291, 504, 73, 1),
(292, 505, 98, 1),
(293, 505, 75, 1),
(294, 354, 86, 1),
(295, 354, 89, 1),
(296, 500, 101, 1),
(297, 500, 107, 1),
(298, 500, 89, 1),
(299, 500, 84, 1),
(301, 518, 107, 1),
(302, 518, 86, 1),
(303, 518, 89, 1),
(304, 518, 101, 1),
(306, 179, 89, 1),
(308, 179, 107, 1),
(311, 517, 89, 1),
(312, 517, 107, 1),
(314, 517, 101, 1),
(315, 517, 86, 1),
(316, 236, 86, 1),
(317, 274, 84, 1),
(319, 174, 89, 1),
(320, 175, 84, 1),
(325, 517, 73, 1),
(326, 278, 116, 1),
(327, 156, 84, 1),
(328, 184, 127, 1),
(329, 184, 89, 1),
(330, 184, 101, 1),
(331, 248, 89, 1),
(332, 321, 88, 1),
(333, 199, 125, 1),
(334, 480, 89, 1),
(335, 480, 127, 1),
(337, 480, 125, 1),
(341, 493, 107, 1),
(342, 493, 101, 1),
(343, 493, 89, 1),
(345, 493, 105, 1),
(346, 506, 98, 1),
(347, 335, 98, 1),
(349, 261, 127, 1),
(351, 520, 107, 1),
(352, 520, 101, 1),
(353, 520, 86, 1),
(354, 520, 89, 1),
(356, 521, 89, 1),
(357, 521, 86, 1),
(358, 521, 107, 1),
(359, 521, 101, 1),
(360, 521, 73, 1),
(361, 355, 125, 1),
(362, 355, 89, 1),
(363, 501, 107, 1),
(364, 501, 101, 1),
(365, 501, 86, 1),
(366, 501, 89, 1),
(367, 501, 84, 1),
(368, 519, 88, 1),
(369, 519, 86, 1),
(370, 519, 101, 1),
(371, 519, 89, 1),
(372, 521, 103, 1),
(373, 502, 84, 1),
(375, 502, 88, 1),
(376, 502, 125, 1),
(377, 502, 105, 1),
(378, 502, 89, 1),
(379, 237, 127, 1),
(380, 272, 127, 1),
(382, 272, 125, 1),
(383, 322, 125, 1),
(384, 533, 135, 1),
(385, 533, 137, 1),
(386, 533, 89, 1),
(389, 529, 107, 1),
(391, 529, 101, 1),
(392, 529, 137, 1),
(393, 529, 89, 1),
(394, 376, 98, 1),
(396, 185, 144, 1),
(399, 185, 89, 1),
(400, 249, 135, 1),
(401, 249, 125, 1),
(403, 533, 86, 1),
(404, 279, 135, 1),
(405, 279, 137, 1),
(406, 481, 137, 1),
(407, 481, 127, 1),
(408, 494, 105, 1),
(409, 494, 146, 1),
(410, 494, 89, 1),
(411, 494, 88, 1),
(412, 214, 137, 1),
(413, 356, 98, 1),
(415, 542, 107, 1),
(416, 542, 73, 1),
(417, 542, 89, 1),
(419, 542, 127, 1),
(420, 542, 96, 1),
(421, 522, 84, 1),
(422, 522, 89, 1),
(424, 529, 86, 1),
(425, 541, 89, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `caderno_anotacoes`
--

CREATE TABLE `caderno_anotacoes` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `conteudo` text NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `caderno_anotacoes`
--

INSERT INTO `caderno_anotacoes` (`id`, `aluno_id`, `titulo`, `conteudo`, `data_criacao`, `data_atualizacao`) VALUES
(3, 18, 'teste', 'testando\r\n', '2025-12-19 11:45:58', '2025-12-19 11:45:58'),
(4, 26, 'verbs ', 'go , get , do , use , share , post', '2025-12-19 18:22:38', '2025-12-19 18:22:38'),
(7, 46, 'Highlights belfast giants', 'https://www.youtube.com/watch?v=Jb90D9q_Q3k ', '2026-01-13 17:58:10', '2026-01-13 17:58:10'),
(9, 37, 'Questionário', 'questions from BBB \r\n', '2026-01-14 21:57:32', '2026-01-14 21:57:32'),
(10, 48, 'structure exercises', 'https://www.perfect-english-grammar.com/grammar-exercises.html', '2026-01-16 12:08:21', '2026-01-16 12:08:21'),
(11, 41, 'grammar exercises', 'https://www.perfect-english-grammar.com/grammar-exercises.html ', '2026-01-16 13:03:24', '2026-01-16 13:03:24'),
(12, 47, 'Site de pesquisa', 'https://pt.youglish.com/pronounce/14/english', '2026-01-16 22:08:42', '2026-01-16 22:08:42'),
(13, 30, 'verbs', 'go, get, have', '2026-01-21 11:11:32', '2026-01-21 11:11:32'),
(15, 39, 'aula 11 março', 'lecture- palestra\r\nspeacker- palestrante\r\nrested\r\nLet me pick/grab/take a glass of water\r\nmother\'s house\r\ncame\r\nmaster\'s thesis committee\r\nmaster\'s thesis defense\r\nwe\'re all in the same boat\r\nTine- minusculo\r\n\r\ndeal, feito', '2026-02-11 14:30:16', '2026-02-11 14:30:16'),
(16, 57, 'vocab', 'rainy, cloudy', '2026-02-13 23:05:54', '2026-02-13 23:05:54');

-- --------------------------------------------------------

--
-- Estrutura para tabela `conteudos`
--

CREATE TABLE `conteudos` (
  `id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `grupo_id` int(11) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo_arquivo` varchar(50) NOT NULL,
  `caminho_arquivo` varchar(255) NOT NULL,
  `data_upload` timestamp NOT NULL DEFAULT current_timestamp(),
  `eh_subpasta` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `conteudos`
--

INSERT INTO `conteudos` (`id`, `professor_id`, `parent_id`, `grupo_id`, `titulo`, `descricao`, `tipo_arquivo`, `caminho_arquivo`, `data_upload`, `eh_subpasta`) VALUES
(65, 22, NULL, NULL, 'Travel', '', 'TEMA', '', '2025-10-20 01:26:50', 0),
(67, 22, 65, NULL, 'Describing cities - level B', 'Describing cities - level B (Link: www.esolcourses.com)', 'URL', 'https://www.esolcourses.com/content/exercises/grammar/adjectives/places/words-for-describing-places.html', '2025-10-20 01:41:42', 0),
(68, 22, 65, NULL, 'Intermediário', '', 'SUBPASTA', '', '2025-10-20 01:44:56', 1),
(69, 22, 68, NULL, 'Top 10 Most Beautiful City to live in USA', 'Top 10 Most Beautiful City to live in USA (Link: www.youtube.com)', 'URL', 'https://www.youtube.com/watch?v=Zu_S2wLqMXM', '2025-10-20 01:45:49', 0),
(70, 22, NULL, NULL, 'Welcome Class', '', 'TEMA', '', '2025-11-29 01:24:08', 0),
(72, 22, 70, NULL, 'Apresentação Risenglish', 'Apresentação Risenglish (Arquivo: Presentation Rise English .pdf)', 'application/pdf', 'uploads/conteudos/1764542381_692cc7ada4f30.pdf', '2025-11-30 22:39:41', 0),
(73, 22, NULL, NULL, 'Aula experimental', '', 'TEMA', '', '2026-01-05 18:48:30', 0),
(74, 22, 73, NULL, 'Interview Test', 'Interview Test (Arquivo: Interview test .pdf)', 'application/pdf', 'uploads/conteudos/1767638958_695c07ae1f70e.pdf', '2026-01-05 18:49:18', 0),
(75, 22, NULL, NULL, '1st classes', '', 'TEMA', '', '2026-01-12 23:23:01', 0),
(77, 22, NULL, NULL, 'Tongue twisters', '', 'TEMA', '', '2026-01-13 00:59:39', 0),
(78, 22, 77, NULL, 'Tongue twisters', 'Tongue twisters (Arquivo: TONGUE TWISTERS.pdf)', 'application/pdf', 'uploads/conteudos/1768265997_6965990d81faf.pdf', '2026-01-13 00:59:57', 0),
(79, 22, 75, NULL, 'Bucket list', 'Bucket list (Arquivo: My bucket list.pdf)', 'application/pdf', 'uploads/conteudos/1768267653_69659f85300c4.pdf', '2026-01-13 01:27:33', 0),
(80, 22, 75, NULL, 'Reading bucket meaning', 'Reading bucket meaning (Link: med.stanford.edu)', 'URL', 'https://med.stanford.edu/letter/bucket-list/what-is-bucket-list.html', '2026-01-14 18:05:17', 0),
(81, 22, 75, NULL, 'The bucket list trailer', 'The bucket list trailer (Link: www.youtube.com)', 'URL', 'https://www.youtube.com/watch?v=UvdTpywTmQg', '2026-01-14 18:05:34', 0),
(82, 22, 75, NULL, 'Bucket list ideas', 'Bucket list ideas (Link: bucketlistjourney.net)', 'URL', 'https://bucketlistjourney.net/my-bucket-list/', '2026-01-15 00:43:12', 0),
(84, 22, NULL, NULL, 'Communication', '', 'TEMA', '', '2026-01-15 20:56:41', 0),
(85, 22, 84, NULL, 'Communication sentences', 'Communication sentences (Arquivo: Communication.pdf)', 'application/pdf', 'uploads/conteudos/1768510616_69695498a442a.pdf', '2026-01-15 20:56:56', 0),
(86, 22, NULL, NULL, 'Numbers', '', 'TEMA', '', '2026-01-16 20:53:18', 0),
(88, 22, NULL, NULL, 'Pronouns', '', 'TEMA', '', '2026-01-16 20:54:32', 0),
(89, 22, NULL, NULL, 'Conversation', '', 'TEMA', '', '2026-01-16 20:54:41', 0),
(92, 22, 75, NULL, 'Conversação After Holidays', 'Conversação After Holidays (Arquivo: After holidays conversation.pdf)', 'application/pdf', 'uploads/conteudos/1768819998_696e0d1eb05d4.pdf', '2026-01-19 10:53:18', 0),
(94, 22, 88, NULL, 'Pronomes básicos', 'Pronomes básicos (Arquivo: Pronouns.pdf)', 'application/pdf', 'uploads/conteudos/1768820056_696e0d58837e4.pdf', '2026-01-19 10:54:16', 0),
(95, 22, 89, NULL, 'Conversation - general', 'Conversation - general (Arquivo: Conversation Rise English materiais.pdf)', 'application/pdf', 'uploads/conteudos/1768820089_696e0d79ad772.pdf', '2026-01-19 10:54:49', 0),
(96, 22, NULL, NULL, 'Possessive \'s', '', 'TEMA', '', '2026-01-19 10:55:08', 0),
(97, 22, 96, NULL, 'Possessive \'s', 'Possessive \'s (Arquivo: Possessive \'s.pdf)', 'application/pdf', 'uploads/conteudos/1768820124_696e0d9cda406.pdf', '2026-01-19 10:55:24', 0),
(98, 22, NULL, NULL, 'Idioms', '', 'TEMA', '', '2026-01-19 22:15:00', 0),
(99, 22, 98, NULL, 'Idioms - general', 'Idioms - general (Arquivo: Idioms - general.pdf)', 'application/pdf', 'uploads/conteudos/1768861020_696ead5c4f6d8.pdf', '2026-01-19 22:17:00', 0),
(100, 22, 98, NULL, 'Idioms website', 'Idioms website (Link: www.espressoenglish.net)', 'URL', 'https://www.espressoenglish.net/100-idioms-meanings-examples/', '2026-01-20 01:58:13', 0),
(101, 22, NULL, NULL, 'Verbs', '', 'TEMA', '', '2026-01-20 20:34:36', 0),
(103, 22, NULL, NULL, 'Conjunctions', '', 'TEMA', '', '2026-01-20 20:35:34', 0),
(104, 22, 103, NULL, 'Basic Conjunctions', 'Basic Conjunctions (Arquivo: Conjunctions.pdf)', 'application/pdf', 'uploads/conteudos/1768941354_696fe72a01479.pdf', '2026-01-20 20:35:54', 0),
(105, 22, NULL, NULL, 'Speech - My Present Routine', '', 'TEMA', '', '2026-01-20 20:53:44', 0),
(107, 22, NULL, NULL, 'Speech - Myself', '', 'TEMA', '', '2026-01-23 01:31:11', 0),
(110, 22, 103, NULL, 'Conjunctions: and, but, or, so, because', 'Conjunctions: and, but, or, so, because (Link: test-english.com)', 'URL', 'https://test-english.com/grammar-points/a1/conjunctions_and-but-or-so-because/', '2026-01-23 01:35:22', 0),
(111, 22, 103, NULL, 'However, although, because, so, and time connectors', 'However, although, because, so, and time connectors (Link: test-english.com)', 'URL', 'https://test-english.com/grammar-points/a2/however-although-time-connectors/', '2026-01-23 01:35:45', 0),
(112, 22, NULL, NULL, 'Introduce yourself', '', 'TEMA', '', '2026-01-29 15:33:19', 0),
(113, 22, 112, NULL, 'How to introduce yourself - business interview', 'How to introduce yourself - business interview (Arquivo: How to introduce yourself.pdf)', 'application/pdf', 'uploads/conteudos/1769700825_697b7dd9562f9.pdf', '2026-01-29 15:33:45', 0),
(115, 22, 107, NULL, 'Myself - basic', 'Myself - basic (Arquivo: Myself Speech.pdf)', 'application/pdf', 'uploads/conteudos/1769824071_697d5f4760494.pdf', '2026-01-31 01:47:51', 0),
(116, 22, NULL, NULL, 'Agribusiness', '', 'TEMA', '', '2026-02-03 19:33:21', 0),
(117, 22, 116, NULL, 'Agribusiness vocabulary - basic', 'Agribusiness vocabulary - basic (Arquivo: Agribusiness vocabulary.pdf)', 'application/pdf', 'uploads/conteudos/1770147225_69824d99bdcb7.pdf', '2026-02-03 19:33:45', 0),
(118, 22, NULL, NULL, 'Speech - My past', '', 'TEMA', '', '2026-02-04 00:53:15', 0),
(119, 22, 118, NULL, 'Lista dos verbos irregulares', 'Lista dos verbos irregulares (Arquivo: lista de verbos irregulares.pdf)', 'application/pdf', 'uploads/conteudos/1770166414_6982988edfa7e.pdf', '2026-02-04 00:53:34', 0),
(120, 22, NULL, NULL, 'Speech - My City', '', 'TEMA', '', '2026-02-04 00:53:59', 0),
(121, 22, 120, NULL, 'My city - basic', 'My city - basic (Arquivo: My city.pdf)', 'application/pdf', 'uploads/conteudos/1770166627_69829963a13a6.pdf', '2026-02-04 00:57:07', 0),
(123, 22, 86, NULL, 'Numbers', 'Numbers (Arquivo: Numbers.pdf)', 'application/pdf', 'uploads/conteudos/1770167422_69829c7eeb868.pdf', '2026-02-04 01:10:22', 0),
(124, 22, 86, NULL, 'Large numbers', 'Large numbers (Link: englishlessonsbrighton.co.uk)', 'URL', 'https://englishlessonsbrighton.co.uk/saying-large-numbers-english/', '2026-02-04 01:10:58', 0),
(125, 22, NULL, NULL, 'Structure', '', 'TEMA', '', '2026-02-04 01:17:45', 0),
(126, 22, 125, NULL, 'Estrutura básica dos tempos verbais', 'Estrutura básica dos tempos verbais (Arquivo: Structure.pdf)', 'application/pdf', 'uploads/conteudos/1770167883_69829e4bda015.pdf', '2026-02-04 01:18:03', 0),
(127, 22, NULL, NULL, 'Modal verbs', '', 'TEMA', '', '2026-02-04 01:25:02', 0),
(129, 22, 118, NULL, 'Pronúncia -ed dos verbos', 'Pronúncia -ed dos verbos (Link: www.englishclub.com)', 'URL', 'https://www.englishclub.com/pronunciation/-ed.php', '2026-02-09 01:08:07', 0),
(130, 22, 127, NULL, 'Modal verbs - basic', 'Modal verbs - basic (Arquivo: Modal Verbs.pdf)', 'application/pdf', 'uploads/conteudos/1770600399_698937cf86c83.pdf', '2026-02-09 01:26:39', 0),
(131, 22, 127, NULL, 'Have to, don’t have to, must, mustn’t', 'Have to, don’t have to, must, mustn’t (Link: test-english.com)', 'URL', 'https://test-english.com/grammar-points/a2/have-to-dont-have-to-must-mustnt/', '2026-02-09 01:27:31', 0),
(132, 22, 127, NULL, 'Should, shouldn’t', 'Should, shouldn’t (Link: test-english.com)', 'URL', 'https://test-english.com/grammar-points/a2/should-shouldnt/', '2026-02-09 01:27:52', 0),
(133, 22, 127, NULL, 'Modal verbs of deduction: Must, may, might, could, can’t', 'Modal verbs of deduction: Must, may, might, could, can’t (Link: test-english.com)', 'URL', 'https://test-english.com/grammar-points/b1/modal-verbs-of-deduction/', '2026-02-09 01:28:26', 0),
(135, 22, NULL, NULL, 'WH words', '', 'TEMA', '', '2026-02-09 01:33:30', 0),
(136, 22, 135, NULL, 'WH words', 'WH words (Arquivo: WH questions.pdf)', 'application/pdf', 'uploads/conteudos/1770600831_6989397fa9387.pdf', '2026-02-09 01:33:51', 0),
(137, 22, NULL, NULL, 'Quantifiers', '', 'TEMA', '', '2026-02-09 01:36:13', 0),
(138, 22, 137, NULL, 'Quantifiers', 'Quantifiers (Arquivo: Quantifiers.pdf)', 'application/pdf', 'uploads/conteudos/1770600992_69893a2089301.pdf', '2026-02-09 01:36:32', 0),
(139, 22, 137, NULL, 'Much, many, little, few, some, any', 'Much, many, little, few, some, any (Link: test-english.com)', 'URL', 'https://test-english.com/grammar-points/a2/much-many-little-few-some-any/', '2026-02-09 01:37:12', 0),
(140, 22, 137, NULL, 'Grammar chart', 'Grammar chart (Link: test-english.com)', 'URL', 'https://test-english.com/grammar-points/b1/much-many-lot-little-no/', '2026-02-09 01:37:45', 0),
(141, 22, 101, NULL, 'Common Verbs', 'Common Verbs (Arquivo: Verbs.pdf)', 'application/pdf', 'uploads/conteudos/1770601824_69893d60f1f60.pdf', '2026-02-09 01:50:24', 0),
(142, 22, 101, NULL, 'Daily routine', 'Daily routine (Link: test-english.com)', 'URL', 'https://test-english.com/vocabulary/a1/daily-routines-a1-english-vocabulary/', '2026-02-09 01:50:51', 0),
(143, 22, 101, NULL, 'Common phrasal verbs', 'Common phrasal verbs (Link: test-english.com)', 'URL', 'https://test-english.com/vocabulary/a2/common-phrasal-verbs-a2-english-vocabulary/', '2026-02-09 01:51:33', 0),
(144, 22, NULL, NULL, 'Speech - Business', '', 'TEMA', '', '2026-02-09 01:53:40', 0),
(145, 22, 144, NULL, 'Business - basic', 'Business - basic (Arquivo: Business speech.pdf)', 'application/pdf', 'uploads/conteudos/1770602038_69893e3656b0d.pdf', '2026-02-09 01:53:58', 0),
(146, 22, NULL, NULL, 'Speech - My House', '', 'TEMA', '', '2026-02-09 01:55:26', 0),
(147, 22, 146, NULL, 'My house', 'My house (Arquivo: My house speech.pdf)', 'application/pdf', 'uploads/conteudos/1770602138_69893e9adce0f.pdf', '2026-02-09 01:55:38', 0),
(148, 22, 146, NULL, 'The House: Rooms, Parts, and Things', 'The House: Rooms, Parts, and Things (Link: test-english.com)', 'URL', 'https://test-english.com/vocabulary/a1/the-house-rooms-parts-and-things-a1-english-vocabulary/', '2026-02-09 01:56:08', 0),
(149, 22, 105, NULL, 'Present - 1st person', 'Present - 1st person (Arquivo: Present speech - 1st person.pdf)', 'application/pdf', 'uploads/conteudos/1770665392_698a35b06ddb8.pdf', '2026-02-09 19:29:52', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `grupos_conteudos`
--

CREATE TABLE `grupos_conteudos` (
  `id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `cor` varchar(20) DEFAULT '#081d40',
  `icone` varchar(50) DEFAULT 'fas fa-layer-group',
  `ordem` int(11) DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `mes_referencia` date NOT NULL COMMENT 'Primeiro dia do mês de referência',
  `valor` decimal(10,2) NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pagamentos`
--

INSERT INTO `pagamentos` (`id`, `aluno_id`, `mes_referencia`, `valor`, `data_pagamento`, `observacoes`, `data_registro`, `data_modificacao`) VALUES
(1, 30, '2025-12-01', 300.00, '2025-12-01', '', '2025-12-18 23:58:00', '2025-12-18 23:58:00'),
(2, 30, '2026-01-01', 300.00, '2026-01-02', '', '2026-01-03 00:03:26', '2026-01-03 00:03:26'),
(3, 27, '2026-01-01', 400.00, '2026-01-02', '', '2026-01-03 01:05:25', '2026-01-03 01:05:25'),
(4, 31, '2026-01-01', 342.00, '2025-12-26', '', '2026-01-03 01:05:46', '2026-01-03 01:05:46'),
(5, 33, '2026-01-01', 250.00, '2026-01-01', '', '2026-01-03 01:06:19', '2026-01-03 01:06:19'),
(6, 26, '2026-01-01', 720.00, '2026-01-05', '', '2026-01-05 10:11:22', '2026-01-05 10:11:22'),
(7, 46, '2026-01-01', 780.00, '2026-01-05', '', '2026-01-05 10:11:35', '2026-01-05 10:11:35'),
(8, 32, '2026-01-01', 250.00, '2026-01-08', '', '2026-01-09 16:17:27', '2026-01-09 16:17:27'),
(9, 42, '2026-01-01', 420.00, '2026-01-05', '', '2026-01-09 16:17:57', '2026-01-09 16:17:57'),
(10, 34, '2026-01-01', 250.00, '2026-01-07', '', '2026-01-09 16:18:12', '2026-01-09 16:18:12'),
(11, 44, '2026-01-01', 280.00, '2026-01-12', '', '2026-01-13 21:42:41', '2026-01-13 21:42:41'),
(12, 43, '2026-01-01', 280.00, '2026-01-12', '', '2026-01-13 21:42:52', '2026-01-13 21:42:52'),
(13, 36, '2026-01-01', 195.00, '2026-01-09', '', '2026-01-13 21:43:19', '2026-01-13 21:43:19'),
(14, 35, '2026-01-01', 195.00, '2026-01-09', '', '2026-01-13 21:43:29', '2026-01-13 21:43:29'),
(15, 37, '2026-01-01', 280.00, '2026-01-07', '', '2026-01-13 21:43:58', '2026-01-13 21:43:58'),
(16, 45, '2026-01-01', 280.00, '2026-01-14', '', '2026-01-14 11:25:08', '2026-01-14 11:25:08'),
(17, 18, '2026-01-01', 280.00, '2026-01-15', '', '2026-01-15 19:53:33', '2026-01-15 19:53:33'),
(18, 47, '2026-01-01', 400.00, '2026-01-13', '', '2026-01-16 17:16:39', '2026-01-16 17:16:39'),
(19, 34, '2026-02-01', 250.00, '2026-01-15', '', '2026-01-16 17:20:27', '2026-01-16 17:20:27'),
(20, 23, '2026-01-01', 1200.00, '2026-01-20', '', '2026-01-21 22:08:12', '2026-01-21 22:08:12'),
(21, 29, '2026-01-01', 600.00, '2026-01-20', '', '2026-01-21 22:08:22', '2026-01-21 22:08:22'),
(22, 52, '2026-01-01', 850.00, '2026-01-19', '', '2026-01-21 22:08:54', '2026-01-21 22:08:54'),
(23, 39, '2026-01-01', 292.00, '2026-01-21', '', '2026-01-25 19:45:38', '2026-01-25 19:45:38'),
(24, 38, '2026-01-01', 292.00, '2026-01-22', '', '2026-01-25 19:45:50', '2026-01-25 19:45:50'),
(25, 28, '2026-01-01', 0.00, '2026-01-02', '', '2026-01-25 19:47:01', '2026-01-25 19:47:01'),
(26, 49, '2026-01-01', 280.00, '2026-01-27', '', '2026-01-27 21:55:18', '2026-01-27 21:55:18'),
(27, 48, '2026-01-01', 280.00, '2026-01-27', '', '2026-01-27 21:55:27', '2026-01-27 21:55:27'),
(28, 30, '2026-02-01', 300.00, '2026-01-30', '', '2026-01-31 01:05:32', '2026-01-31 01:05:32'),
(29, 31, '2026-02-01', 456.00, '2026-01-30', '', '2026-01-31 01:05:48', '2026-01-31 01:05:48'),
(30, 54, '2026-01-01', 0.00, '2026-01-31', '', '2026-01-31 15:04:35', '2026-01-31 15:04:35'),
(31, 53, '2026-01-01', 400.00, '2026-02-02', '', '2026-02-02 15:04:44', '2026-02-02 15:04:44'),
(32, 26, '2026-02-01', 720.00, '2026-02-03', '', '2026-02-04 00:46:13', '2026-02-04 00:46:13'),
(33, 42, '2026-02-01', 320.00, '2026-02-03', '', '2026-02-04 00:46:24', '2026-02-04 00:46:24'),
(34, 43, '2026-02-01', 280.00, '2026-02-03', '', '2026-02-04 00:46:41', '2026-02-04 00:46:41'),
(35, 44, '2026-02-01', 280.00, '2026-02-03', '', '2026-02-04 00:46:55', '2026-02-04 00:46:55'),
(36, 46, '2026-02-01', 780.00, '2026-02-03', '', '2026-02-04 00:47:27', '2026-02-04 00:47:27'),
(37, 27, '2026-02-01', 400.00, '2026-02-05', '', '2026-02-05 22:23:35', '2026-02-05 22:23:35'),
(38, 35, '2026-02-01', 260.00, '2026-02-06', '', '2026-02-06 18:42:09', '2026-02-06 18:42:09'),
(39, 33, '2026-02-01', 250.00, '2026-02-06', '', '2026-02-06 18:42:41', '2026-02-06 18:42:41'),
(40, 57, '2026-02-01', 400.00, '2026-02-07', '', '2026-02-07 19:26:33', '2026-02-07 19:26:33'),
(41, 32, '2026-02-01', 250.00, '2026-02-07', '', '2026-02-07 19:26:44', '2026-02-07 19:26:44'),
(42, 54, '2026-02-01', 300.00, '2026-02-07', '', '2026-02-07 19:27:05', '2026-02-07 19:27:05'),
(43, 36, '2026-02-01', 260.00, '2026-02-06', '', '2026-02-07 19:30:42', '2026-02-07 19:30:42'),
(44, 37, '2026-02-01', 280.00, '2026-02-09', '', '2026-02-09 18:29:42', '2026-02-09 18:29:42'),
(45, 45, '2026-02-01', 280.00, '2026-02-10', '', '2026-02-10 17:17:38', '2026-02-10 17:17:38'),
(46, 28, '2026-02-01', 0.00, '2026-02-10', '', '2026-02-10 17:17:52', '2026-02-10 17:17:52'),
(47, 47, '2026-02-01', 400.00, '2026-02-13', '', '2026-02-14 18:01:31', '2026-02-14 18:01:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `presenca_aula`
--

CREATE TABLE `presenca_aula` (
  `id` int(11) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `presente` tinyint(1) NOT NULL DEFAULT 1,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `presenca_aula`
--

INSERT INTO `presenca_aula` (`id`, `aula_id`, `aluno_id`, `presente`, `data_registro`) VALUES
(10, 49, 34, 0, '2025-11-25 23:32:38'),
(11, 91, 42, 0, '2025-12-01 19:03:38'),
(12, 144, 38, 0, '2025-12-14 17:35:58'),
(13, 144, 39, 0, '2025-12-14 17:35:59'),
(14, 79, 31, 1, '2025-12-19 01:08:07'),
(15, 133, 26, 0, '2025-12-19 21:21:07'),
(16, 303, 30, 1, '2026-01-12 23:24:00'),
(17, 331, 36, 1, '2026-01-13 01:00:40'),
(18, 331, 35, 1, '2026-01-13 01:00:41'),
(19, 233, 46, 1, '2026-01-13 20:26:37'),
(20, 197, 26, 1, '2026-01-13 20:26:44'),
(21, 157, 32, 1, '2026-01-13 23:33:24'),
(22, 157, 33, 1, '2026-01-13 23:33:26'),
(23, 340, 36, 1, '2026-01-15 00:00:37'),
(24, 340, 35, 1, '2026-01-15 00:00:38'),
(25, 296, 31, 1, '2026-01-15 11:40:13'),
(26, 258, 46, 1, '2026-01-15 18:08:30'),
(27, 210, 26, 1, '2026-01-15 19:17:27'),
(28, 165, 23, 1, '2026-01-15 19:19:37'),
(29, 373, 23, 1, '2026-01-15 19:20:30'),
(30, 276, 43, 1, '2026-01-15 22:14:53'),
(31, 276, 44, 1, '2026-01-15 22:14:57'),
(32, 270, 28, 1, '2026-01-16 00:19:22'),
(33, 270, 27, 1, '2026-01-16 00:19:23'),
(34, 190, 48, 1, '2026-01-16 12:10:51'),
(35, 171, 42, 1, '2026-01-16 16:26:25'),
(36, 222, 26, 1, '2026-01-16 18:10:15'),
(37, 426, 47, 1, '2026-01-16 23:02:44'),
(38, 409, 23, 1, '2026-01-16 23:30:41'),
(39, 352, 41, 1, '2026-01-16 23:31:00'),
(40, 304, 30, 1, '2026-01-19 14:16:12'),
(41, 467, 52, 1, '2026-01-19 16:59:05'),
(42, 234, 46, 1, '2026-01-19 18:04:30'),
(43, 422, 23, 1, '2026-01-19 19:00:31'),
(44, 476, 29, 1, '2026-01-19 21:55:46'),
(45, 319, 36, 1, '2026-01-20 00:13:56'),
(46, 319, 35, 1, '2026-01-20 00:13:56'),
(47, 297, 31, 1, '2026-01-20 12:02:14'),
(48, 158, 33, 0, '2026-01-20 20:56:02'),
(49, 423, 23, 0, '2026-01-20 23:19:02'),
(50, 182, 38, 0, '2026-01-21 16:28:09'),
(51, 182, 39, 0, '2026-01-21 16:28:10'),
(52, 424, 23, 0, '2026-01-21 21:56:26'),
(53, 362, 23, 0, '2026-01-26 18:43:07'),
(54, 180, 42, 0, '2026-01-26 21:56:59'),
(55, 159, 33, 0, '2026-01-27 18:34:02'),
(56, 183, 38, 0, '2026-01-28 14:06:58'),
(57, 183, 39, 0, '2026-01-28 14:06:59'),
(58, 181, 39, 0, '2026-01-28 14:07:10'),
(59, 181, 38, 0, '2026-01-28 14:07:10'),
(60, 428, 52, 0, '2026-02-03 11:33:33'),
(61, 437, 52, 0, '2026-02-03 11:33:39'),
(62, 445, 52, 0, '2026-02-03 11:33:44'),
(63, 453, 52, 0, '2026-02-03 11:33:49'),
(64, 461, 52, 0, '2026-02-03 11:33:54'),
(65, 363, 23, 0, '2026-02-03 11:34:06'),
(66, 411, 23, 0, '2026-02-03 11:34:12'),
(67, 398, 23, 0, '2026-02-03 11:34:17'),
(68, 387, 23, 0, '2026-02-04 19:15:58'),
(69, 375, 23, 0, '2026-02-04 19:16:03'),
(70, 412, 23, 0, '2026-02-07 01:02:35'),
(71, 310, 30, 0, '2026-02-08 18:52:20'),
(72, 175, 42, 0, '2026-02-10 17:14:46'),
(73, 376, 23, 0, '2026-02-10 22:33:31'),
(74, 388, 23, 0, '2026-02-11 19:50:52'),
(75, 475, 29, 0, '2026-02-11 22:04:18'),
(76, 225, 26, 0, '2026-02-11 22:07:09'),
(77, 506, 37, 0, '2026-02-12 00:28:32');

-- --------------------------------------------------------

--
-- Estrutura para tabela `recursos_uteis`
--

CREATE TABLE `recursos_uteis` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `link` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `recursos_uteis`
--

INSERT INTO `recursos_uteis` (`id`, `titulo`, `link`, `descricao`, `data_criacao`) VALUES
(6, 'DeepL', 'https://www.deepl.com/pt-BR/translator', 'Serviço de tradução que utiliza IA para oferecer traduções de qualidade.', '2025-10-16 10:07:00'),
(7, 'toPhonetics', 'https://tophonetics.com/', 'Conversor de textos em Inglês para sua tradução fonética.', '2025-10-16 10:07:44'),
(8, 'Youglish', 'https://pt.youglish.com/', 'O YouGlish te dá respostas rápidas sobre como o inglês é falado por pessoas reais dentro de um contexto.', '2025-10-16 10:08:26'),
(9, 'Thesaurus', 'https://www.thesaurus.com/', 'Dicionário para pesquisa de sinônimos e antônimos das paalvras.', '2025-11-12 01:14:18'),
(10, 'Babadum', 'https://babadum.com/', 'Site de jogo online de palavras em inglês. Ótimo para expandir vocabulário.', '2025-12-10 19:26:13'),
(11, 'Linguee - dicionário', 'https://www.linguee.com.br/', 'Dicionário inglês-português e buscador de traduções.', '2025-12-17 22:14:11'),
(12, 'Speak Languages', 'https://www.speaklanguages.com/english/phrases/', 'Site com diversas frases com áudios úteis do dia a dia, para praticar fluência e vocabulário.', '2026-01-15 19:49:29'),
(13, 'Calendário da escola 2026', 'https://drive.google.com/drive/folders/1VIShB-WR7YpDa7x7SEWiBANaSlEAO1tc?usp=sharing', 'Calendário das aulas de 2026. Se atentem aos feriados e dias da Copa do Mundo.', '2026-01-25 20:21:54'),
(14, 'Test-English vocabulário', 'https://test-english.com/vocabulary/', 'Site para desenvolvimento de vocabulário; separado por níveis e tópicos. Além da lista de palavras, tem exercícios de cada tema com correção automática.', '2026-01-25 20:24:35'),
(15, 'Perfect English Grammar', 'https://www.perfect-english-grammar.com/grammar-explanations.html', 'Explicações e exercícios de gramática.', '2026-01-25 20:26:02'),
(16, 'Bob the Canadian - listening', 'https://youtube.com/@learnenglishwithbobthecanadian?si=6CH3RnstbfSneKK7', 'Bob the Canadian é um canal para praticar listening e aprender muitos costumes e vocabulário. Indicado para nível A2 em diante.', '2026-01-25 20:31:14'),
(17, 'News in Levels', 'https://www.newsinlevels.com/', 'Notícias mundiais separadas por níveis, sendo level 1 o mais fácil e level 3 o mais sofisticado. Prática de leitura e escuta (áudios juntos com os textos).', '2026-01-25 20:34:06'),
(18, 'BBC - 6 Minute English', 'https://www.bbc.co.uk/learningenglish/english/features/6-minute-english', 'Podcast de 6 minutos sobre temas variados feito pela BBC. Sotaque britânico e indicado para nível intermediário.', '2026-02-12 00:29:38');

-- --------------------------------------------------------

--
-- Estrutura para tabela `turmas`
--

CREATE TABLE `turmas` (
  `id` int(11) NOT NULL,
  `nome_turma` varchar(100) NOT NULL,
  `professor_id` int(11) DEFAULT NULL,
  `inicio_turma` date NOT NULL,
  `link_aula` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `turmas`
--

INSERT INTO `turmas` (`id`, `nome_turma`, `professor_id`, `inicio_turma`, `link_aula`) VALUES
(10, 'Caio Dela Marta', 22, '2026-01-14', 'https://us06web.zoom.us/j/89065011146?pwd=s8IbWmVth9YsFo00b76t7hb0CPvKjW.1'),
(11, 'Jorge Pontes', 22, '2025-10-26', 'https://us06web.zoom.us/j/88437268796?pwd=WHlh7A9ziDIuzYddwkI3HScIhDyFHM.1'),
(12, 'Lucas e Lucas', 22, '2025-10-20', 'https://us06web.zoom.us/j/83137402465?pwd=5q4kTy0GGl062eqelMqPlpg8GJje3S.1'),
(13, 'Bruna Carolina', 22, '2025-10-24', 'https://us06web.zoom.us/j/82755409355?pwd=PajbGacDbXMohe2dp9J8xZpK93YZiY.1'),
(14, 'Beatriz e Eliana', 22, '2025-10-26', 'https://us06web.zoom.us/j/87265410498?pwd=MoXtUE4HPS0dMx0aaWnpFNLUKp1fOd.1'),
(15, 'Alice Guilhoto', 22, '2025-10-26', 'https://us06web.zoom.us/j/84404465481?pwd=rojlwPbkM8NsCnoF3kMaxQHvAIaay2.1'),
(16, 'Nicky Bryan', 22, '2025-10-26', 'https://us06web.zoom.us/j/85354089186?pwd=feS51hbn0sq6DCsqggG4Znuj32BDEq.1'),
(17, 'Manuela Antero', 22, '2026-01-14', ''),
(18, 'Kleber ', 22, '2025-10-26', 'https://us06web.zoom.us/j/85957044703?pwd=uDwDEAvDtcevtjSTLHBq0FZVg4ONFj.1'),
(19, 'Giovanna e Murilo', 22, '2025-10-26', 'https://us06web.zoom.us/j/83798302377?pwd=kcpMPluACTbD2t2PPWq1XgUeEKYNGV.1'),
(20, 'Pietra e Lucas', 22, '2025-10-26', 'https://us06web.zoom.us/j/89794777558?pwd=UMHfarXrgIzuBFlVmWvhyeqi8FNdip.1'),
(21, 'Fabiane Coelho', 22, '2026-01-13', 'https://us06web.zoom.us/j/89898786697?pwd=OnV3NPMgX3BbCbBJsh47FzUHoqdXNy.1 '),
(22, 'Isabela Rossa', 22, '2026-01-14', 'https://us06web.zoom.us/j/81393493789?pwd=osm8qKT3LqrzxjlCaNR2hewKVqakIk.1'),
(23, 'Priscila Meira', 22, '2025-10-26', 'https://us06web.zoom.us/j/88106358840?pwd=Bb1ZvXib9DkXUZvQarMZ1M5qevSUO7.1'),
(24, 'Boss Ladies Club', 22, '2025-10-26', 'https://us06web.zoom.us/j/83407724425?pwd=UGVkDOlF8HxtA3CExOv9nk9FaXi8jy.1'),
(26, 'Sônia Oliveira', 22, '2025-10-26', 'https://us06web.zoom.us/j/89755530592?pwd=YTSyHp2zcJXkZ2q6q6HelSrIbBBcby.1'),
(27, 'Bruna Muraro', 22, '2025-10-26', 'https://us06web.zoom.us/j/87815893475?pwd=lgstJ9L7OyBuCXsSy3ZJ22Z3z1bXME.1'),
(28, 'Daniel e Leandro', 22, '2025-10-26', 'https://us06web.zoom.us/j/82177636661?pwd=ZSbkpZW8Nud0AfNsx5o0yHLHxxY9KA.1'),
(29, 'Yasmin Marmo', 22, '2025-10-26', 'https://us06web.zoom.us/j/88043030586?pwd=C9ofB9QWHjuazYDUdrYdPZetKlm6ji.1'),
(30, 'Ana e Diego', 22, '2025-10-26', 'https://us06web.zoom.us/j/85146980290?pwd=FibvKTQsc3ucuFP5Aq0Lk0UVdDOgAC.1'),
(31, 'Tathiane Saraiva', 22, '2026-01-23', 'https://us06web.zoom.us/j/85187564483?pwd=VlAqGKhuq5X3FID96PVOcr2UYaDnia.1'),
(32, 'Aulas teste', 22, '2026-01-05', ''),
(33, 'Larissa Eduarda', 22, '2026-01-16', 'https://us06web.zoom.us/j/89263703095?pwd=YE9TA6M0tzn4dn4az7wutYUBp2VQnM.1'),
(34, 'Thiago Zattoni', 22, '2026-01-19', ' https://us06web.zoom.us/j/87924647193?pwd=2vYqIbk6aoDM42CBX1C77Nz3ET2Xxd.1'),
(35, 'Rafael Gogola', 22, '2026-01-27', 'https://us06web.zoom.us/j/84751787476?pwd=bjZOmvYbfvjGhCUVFaP9aCAIvZMbX4.1'),
(36, 'Gabrielly', 22, '2026-01-28', 'https://us06web.zoom.us/j/89225028348?pwd=xBaEWVxatAX1a3XXpGM1wjbHNAacfl.1'),
(37, 'Guilherme Prado', 22, '2026-02-06', 'https://us06web.zoom.us/j/84752165832?pwd=IajfjTuAc1m8mc4802e1QzFoBLcptO.1'),
(38, 'Ana Clara', 22, '2026-02-06', 'https://us06web.zoom.us/j/84953277263?pwd=bgsTKaLKvjUcjJKtddX7W0fIjD3OGB.1');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo_usuario` enum('admin','professor','aluno') NOT NULL,
  `informacoes` text DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `token_expira_em` datetime DEFAULT NULL,
  `dia_vencimento` int(2) DEFAULT NULL COMMENT 'Dia do mês para vencimento (1-31)',
  `status` enum('ativo','desativado') NOT NULL DEFAULT 'ativo',
  `responsavel_financeiro_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo_usuario`, `informacoes`, `reset_token`, `token_expira_em`, `dia_vencimento`, `status`, `responsavel_financeiro_id`) VALUES
(1, 'Admin Risenglish', 'admin@risenglish.com', '$2y$10$hrBdfKT3yhNJXaajbwZ.B.v4463iiA0r3W8eRfwrcTk614iAI15Ga', 'admin', NULL, NULL, NULL, NULL, 'ativo', NULL),
(18, 'Jorge Pontes', 'jorgeappontes13@gmail.com', '$2y$10$vxLOFkZRQPYQ.lEiRcdME.NdN7/D0krqWDsPmf6KyV/JlR4aQCtEu', 'aluno', '', '45095f753627328b67c0f2825f767562288e8d096da38df756765cab72e03fb7', '2025-12-01 18:58:02', 15, 'ativo', NULL),
(22, 'Laura Barszcz Antero', 'teacherlauraantero@gmail.com', '$2y$10$s5e7DpC7sUizHVvFjTGSzu0G1sVij8zBXs3Xe5HgpbcMhmKjeooJy', 'professor', '', NULL, NULL, NULL, 'ativo', NULL),
(23, 'Caio Henrique Bernal Dela Marta', 'caiobernaldelamarta@hotmail.com', '$2y$10$CWhd3VJ9z0lwd2PPcOPNQunENxRhoPNzk9E//Tt3aoxTwEGueLY6W', 'aluno', '', NULL, NULL, 19, 'ativo', NULL),
(24, 'Lucas Pegoraro Guimarães', 'lucasosspeg@hotmail.com', '$2y$10$69aLE6UeOvl1WiErcbVSVeJTZSmxDqwfZPwI8B0XUerH9.hErVYeC', 'aluno', '', NULL, NULL, NULL, 'ativo', NULL),
(25, 'Lucas Carreira', 'lucasbcarreira@yahoo.com.br', '$2y$10$KMKoYBE3G0j7gxngI/FSLeBa1BZ9NcWMVYmaStddVoxyF3AsiXedi', 'aluno', '', NULL, NULL, NULL, 'ativo', NULL),
(26, 'Bruna Carolina Cestari', 'brunacarolinacestari@gmail.com', '$2y$10$f93Ckg.NZMnGnVKybcGw7OL.s9fysVW9jbz/so8DgUnhNMtooY/qS', 'aluno', '', NULL, NULL, 8, 'ativo', NULL),
(27, 'Murilo Marins', 'murilommarins@gmail.com', '$2y$10$j97TvOk5c56ftbfQC/ajEuONx2I0PYbnXKbvJlRFA0cdkmn.sQ.1K', 'aluno', '', NULL, NULL, 10, 'ativo', NULL),
(28, 'Giovanna Malerba', 'giovannamalerba1105@gmail.com', '$2y$10$SCOUDQb7U.DT/6Lc3g/gjejaqtINl4ZZ9MnMw90JSAj5UOKRHivsa', 'aluno', '', NULL, NULL, 10, 'ativo', 27),
(29, 'Kleber Viana', 'vianak10@gmail.com', '$2y$10$YPGNvhlfhOw4WWYwEklbDuHKl4NxtdAmkpW51FtsZazHWolIkf7kq', 'aluno', '', NULL, NULL, 15, 'ativo', NULL),
(30, 'Alice Guilhoto', 'alice_guilhoto@hotmail.com', '$2y$10$UFrM8BLjfRQQutX2vI6P.eLJ3uMFUPO43cN8eZgz8X4R0.UopIdzu', 'aluno', '', NULL, NULL, 1, 'ativo', NULL),
(31, 'Fabiane Coelho', 'fabiannearaujo@yahoo.com.br', '$2y$10$8hZ1Qoc0WMTWgjCqWgoY6Oa.872wLQtfb6FZlMJkHuXK6G3DONC5e', 'aluno', '', NULL, NULL, 1, 'ativo', NULL),
(32, 'Ariane Perussi', 'arianeperussi@icloud.com', '$2y$10$GavnrcWeFbp5L.1akrZz7OUm4droRG9SscjcimfzYDjDwGe4gIKZ2', 'aluno', '', NULL, NULL, 7, 'ativo', NULL),
(33, 'Mariana Monezi Borzi', 'mmborzi@gmail.com', '$2y$10$7bMH1iS6lTsuiIneTXCPZ.GClHs9gCSS8oNGOLyHPeqkJjlGR6kie', 'aluno', '', NULL, NULL, 7, 'ativo', NULL),
(34, 'Mariele Alves Fernandes', 'marielevi2@icloud.com', '$2y$10$QJA9ltGpjoLmTJuwiAF6reuTmcBmoY6x4JcfU3n/lMRjRTkRyWxK6', 'aluno', '', NULL, NULL, 7, 'ativo', NULL),
(35, 'Pietra Seibt', 'pietraseibt@gmail.com', '$2y$10$U5z0AQVMizeuoxcVay9NxOokx37I1fVDdJ5g0xKRqmBHo/uyF9/nC', 'aluno', '', NULL, NULL, 10, 'ativo', NULL),
(36, 'Lucas Ferreira', 'lucas.personat@hotmail.com', '$2y$10$eH7dpatIUM.M21YfTBFQXub5GDgJgWheuaMdPfFLjbmjApAkHloem', 'aluno', '', NULL, NULL, 10, 'ativo', NULL),
(37, 'Yasmin Marmo', 'marmoyasmin13@gmail.com', '$2y$10$cI/qdvTXEHvSXXSRR11noehwBPhPH.pAY5BY7lx2L6iUWt9O7xcwO', 'aluno', '', NULL, NULL, 7, 'ativo', NULL),
(38, 'Beatriz Gadia', 'biagadia@gmail.com', '$2y$10$DWvjNCIlXq/vlyoCCanEAezW3R5VToOXK5puVMFup1suNKAw8i31O', 'aluno', '', NULL, NULL, 15, 'ativo', NULL),
(39, 'Eliana Chubaci', 'li_chubaci@hotmail.com', '$2y$10$aOQOMRDlpoZzCD3Ia8/d9eYIrdfeYs9llZQroQh2ktH9siC2zKQSW', 'aluno', '', NULL, NULL, 15, 'ativo', NULL),
(40, 'Priscila Meira de Oliveira', 'priscila_meira@yahoo.com.br', '$2y$10$15eSMYWdytHHBpt/rRAwCueR0gR3yMk5nwSrdeUkxkS2ZeT8zFp/W', 'aluno', '', NULL, NULL, NULL, 'desativado', NULL),
(41, 'Tathiane Saraiva', 'tathisaraiva@hotmail.com', '$2y$10$tgMbN0OUDdD/LDY02IzSS.MqBAuHY2zSGp.34rPzdpEUZlos20JZu', 'aluno', '', NULL, NULL, NULL, 'ativo', NULL),
(42, 'Isabela Rossa', 'contatoisaat@gmail.com', '$2y$10$PhG6.se7V5NU4lmL/hD0yeZtfRoT./63QaGqh8noQPDhFQ1Ioe4S.', 'aluno', '', NULL, NULL, 2, 'ativo', NULL),
(43, 'Leandro Hipólito', 'leandrohipolitosilva@hotmail.com', '$2y$10$6aRB8iTQ7PsxHONoBRqtdejbpWv2oZABqKcs2u/5Q045hxbKAv2I2', 'aluno', '', NULL, NULL, 10, 'ativo', NULL),
(44, 'Daniel Maler', 'malerdaniel791@gmail.com', '$2y$10$p.DH/7jjMI1nA2oToqztb.EIHqzLfB1pNhE/L7UZI7GGPfOy7oDVi', 'aluno', '', NULL, NULL, 10, 'ativo', NULL),
(45, 'Bruna Muraro', 'murarobruna@gmail.com', '$2y$10$QoEoAechhIO3C7M2lEtYj.vfk7OE30makPojmId.vTMo7Ku8BKygu', 'aluno', '', NULL, NULL, 10, 'ativo', NULL),
(46, 'Nicky Bryan Lemos', 'nicky.bryanlemes@icloud.com', '$2y$10$wS4iVV37TbHk.eKOdCMpH.ehED6PkoB1vbTFgqwLDtp2TZD6X6cqK', 'aluno', '', NULL, NULL, 8, 'ativo', NULL),
(47, 'Larissa Eduarda Angelico', 'larieduarda8@gmail.com', '$2y$10$PjS2F80HFcPxo5MlntLWTuTHVhmvwzpfjO8V3P2JTCICmjWvWS53G', 'aluno', '', NULL, NULL, 13, 'ativo', NULL),
(48, 'Diego Pilatti', 'diegopilatti@gmail.com', '$2y$10$a/G9Kymc/jqsuPuOm93unO44YgXswigr5hgPi9.JX8zWl5ldx5XgK', 'aluno', '', NULL, NULL, 25, 'ativo', NULL),
(49, 'Ana Paitach', 'anapaitach@generallis.com.br', '$2y$10$NKRwBW/JQ93uE44XT999guXMukoG0QxXtK7oZQNjQWrijAKewSNAK', 'aluno', '', NULL, NULL, 25, 'ativo', NULL),
(52, 'Thiago Luiz Zattoni', 'thiagolzattoni@gmail.com', '$2y$10$1xmL1l2aoXi.OW7QUTH8Y.qxIZ6NSb/SmRG17OQn5x6mjqVMrFApG', 'aluno', '', NULL, NULL, 20, 'ativo', NULL),
(53, 'Rafael Gogola', 'gogola93@hotmail.com', '$2y$10$6XEhuDs9eZu1E81/0xHSoOqwkGcCxMy64UKOMxklgj2fmNL/dOXGK', 'aluno', '', NULL, NULL, 30, 'ativo', NULL),
(54, 'Gabrielly Rosa de Lima', 'gaby.r.lima07@gmail.com', '$2y$10$bwUOFANj0jZzR193gfQ3H.vwNxaFg.Ynaz3/7I51KOcA4aT2Aj7pa', 'aluno', '', NULL, NULL, 7, 'ativo', NULL),
(55, 'Sônia Oliveira', 'soniaoliv@gmail.com', '$2y$10$mjTcjlfJGzni6iGjhng2O.ARjtVlItwfEU3lNkLrsdDgt6yrHoGNC', 'aluno', '', NULL, NULL, 15, 'ativo', NULL),
(56, 'Guilherme Fabiano Batista do Prado', 'gfprado@hotmail.com', '$2y$10$rdzF4xOnQm2a4CWC9yB8PuLuO/51paH0fCZwf7XF/TRHQYzn4jzUq', 'aluno', '', NULL, NULL, NULL, 'ativo', NULL),
(57, 'Ana Clara Nazaret Antônio', 'anaclaranazaret@hotmail.com', '$2y$10$YPMRUqGNuAPh.3ZFNAb7ueJKR0lehDz2CEeQZi/kcomh7b.XcIFwi', 'aluno', '', NULL, NULL, 6, 'ativo', NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `alunos_turmas`
--
ALTER TABLE `alunos_turmas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aluno_id` (`aluno_id`,`turma_id`),
  ADD KEY `turma_id` (`turma_id`);

--
-- Índices de tabela `anotacoes_aula`
--
ALTER TABLE `anotacoes_aula`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aula_aluno_unique` (`aula_id`,`aluno_id`),
  ADD KEY `aluno_id` (`aluno_id`);

--
-- Índices de tabela `anotacoes_itens`
--
ALTER TABLE `anotacoes_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anotacao_id` (`anotacao_id`);

--
-- Índices de tabela `anotacoes_visualizacoes`
--
ALTER TABLE `anotacoes_visualizacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anotacao_id` (`anotacao_id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Índices de tabela `arquivos_visiveis`
--
ALTER TABLE `arquivos_visiveis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aula_conteudo_unique` (`aula_id`,`conteudo_id`),
  ADD KEY `aula_id` (`aula_id`),
  ADD KEY `conteudo_id` (`conteudo_id`);

--
-- Índices de tabela `aulas`
--
ALTER TABLE `aulas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Índices de tabela `aulas_conteudos`
--
ALTER TABLE `aulas_conteudos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aula_id` (`aula_id`,`conteudo_id`),
  ADD KEY `conteudo_id` (`conteudo_id`);

--
-- Índices de tabela `caderno_anotacoes`
--
ALTER TABLE `caderno_anotacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`);

--
-- Índices de tabela `conteudos`
--
ALTER TABLE `conteudos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `conteudos_ibfk_2` (`grupo_id`);

--
-- Índices de tabela `grupos_conteudos`
--
ALTER TABLE `grupos_conteudos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Índices de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aluno_mes_unique` (`aluno_id`,`mes_referencia`),
  ADD KEY `idx_mes_referencia` (`mes_referencia`),
  ADD KEY `idx_aluno_mes` (`aluno_id`,`mes_referencia`);

--
-- Índices de tabela `presenca_aula`
--
ALTER TABLE `presenca_aula`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aula_aluno_unique` (`aula_id`,`aluno_id`),
  ADD KEY `aluno_id` (`aluno_id`);

--
-- Índices de tabela `recursos_uteis`
--
ALTER TABLE `recursos_uteis`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `turmas`
--
ALTER TABLE `turmas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_reset_token` (`reset_token`),
  ADD KEY `fk_responsavel` (`responsavel_financeiro_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alunos_turmas`
--
ALTER TABLE `alunos_turmas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT de tabela `anotacoes_aula`
--
ALTER TABLE `anotacoes_aula`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT de tabela `anotacoes_itens`
--
ALTER TABLE `anotacoes_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT de tabela `anotacoes_visualizacoes`
--
ALTER TABLE `anotacoes_visualizacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `arquivos_visiveis`
--
ALTER TABLE `arquivos_visiveis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `aulas`
--
ALTER TABLE `aulas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=545;

--
-- AUTO_INCREMENT de tabela `aulas_conteudos`
--
ALTER TABLE `aulas_conteudos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=427;

--
-- AUTO_INCREMENT de tabela `caderno_anotacoes`
--
ALTER TABLE `caderno_anotacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `conteudos`
--
ALTER TABLE `conteudos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT de tabela `grupos_conteudos`
--
ALTER TABLE `grupos_conteudos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de tabela `presenca_aula`
--
ALTER TABLE `presenca_aula`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de tabela `recursos_uteis`
--
ALTER TABLE `recursos_uteis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `turmas`
--
ALTER TABLE `turmas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `alunos_turmas`
--
ALTER TABLE `alunos_turmas`
  ADD CONSTRAINT `alunos_turmas_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alunos_turmas_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `anotacoes_aula`
--
ALTER TABLE `anotacoes_aula`
  ADD CONSTRAINT `anotacoes_aula_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `anotacoes_aula_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `anotacoes_itens`
--
ALTER TABLE `anotacoes_itens`
  ADD CONSTRAINT `anotacoes_itens_ibfk_1` FOREIGN KEY (`anotacao_id`) REFERENCES `anotacoes_aula` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `anotacoes_visualizacoes`
--
ALTER TABLE `anotacoes_visualizacoes`
  ADD CONSTRAINT `anotacoes_visualizacoes_ibfk_1` FOREIGN KEY (`anotacao_id`) REFERENCES `anotacoes_aula` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `anotacoes_visualizacoes_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `arquivos_visiveis`
--
ALTER TABLE `arquivos_visiveis`
  ADD CONSTRAINT `arquivos_visiveis_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `arquivos_visiveis_ibfk_2` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `aulas`
--
ALTER TABLE `aulas`
  ADD CONSTRAINT `aulas_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aulas_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aulas_ibfk_3` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `aulas_conteudos`
--
ALTER TABLE `aulas_conteudos`
  ADD CONSTRAINT `aulas_conteudos_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aulas_conteudos_ibfk_2` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `caderno_anotacoes`
--
ALTER TABLE `caderno_anotacoes`
  ADD CONSTRAINT `caderno_anotacoes_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `conteudos`
--
ALTER TABLE `conteudos`
  ADD CONSTRAINT `conteudos_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conteudos_ibfk_2` FOREIGN KEY (`grupo_id`) REFERENCES `grupos_conteudos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `grupos_conteudos`
--
ALTER TABLE `grupos_conteudos`
  ADD CONSTRAINT `grupos_conteudos_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD CONSTRAINT `pagamentos_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `presenca_aula`
--
ALTER TABLE `presenca_aula`
  ADD CONSTRAINT `presenca_aula_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `presenca_aula_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `turmas`
--
ALTER TABLE `turmas`
  ADD CONSTRAINT `turmas_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_responsavel` FOREIGN KEY (`responsavel_financeiro_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
