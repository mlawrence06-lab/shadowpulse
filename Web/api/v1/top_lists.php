<?php
// top_lists.php
// Returns Top lists for Members (Authors), Boards, Topics, and Posts.

require_once 'cors.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$limit = isset($_GET['limit']) ? min((int) $_GET['limit'], 100) : 20;

try {
    $pdo = sp_get_pdo();
    $data = [];

    switch ($action) {
        case 'members':
            // "Members" (Bitcointalk Authors) by Average Post Score
            $stmt = $pdo->prepare("
                SELECT cm.author_id, COUNT(*) as vote_count, AVG(CAST(v.effective_value AS DECIMAL(10,4))) as avg_score
                FROM votes v
                JOIN content_metadata cm ON v.target_id = cm.post_id
                WHERE v.vote_category = 'post'
                AND cm.author_id > 0
                GROUP BY cm.author_id
                ORDER BY avg_score DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $data[] = [
                    'id' => (int) $r['author_id'],
                    'label' => (string) $r['author_id'],
                    'count' => (int) $r['vote_count'],
                    'avg' => round((float) $r['avg_score'], 2)
                ];
            }
            break;

        case 'boards':
            // Top Boards by Avg Score
            // MAP FROM scripts/core/pageContext.js
            $boardNames = [
                1 => "Bitcoin Discussion",
                12 => "Project Development",
                4 => "Bitcoin Technical Support",
                5 => "Marketplace",
                6 => "Development & Technical Discussion",
                7 => "Economics",
                8 => "Trading Discussion",
                9 => "Off-topic",
                10 => "Pycc??? (Russian)",
                11 => "Other languages/locations",
                13 => "Français",
                14 => "Mining",
                16 => "Deutsch (German)",
                17 => "Chinese students",
                18 => "Pa??oe",
                20 => "Tpe??ep?",
                21 => "Ma??ep?",
                22 => "Ho?????",
                23 => "????ec",
                24 => "Meta",
                25 => "Obsolete (buying)",
                26 => "Obsolete (selling)",
                27 => "Español (Spanish)",
                28 => "Italiano (Italian)",
                29 => "Português (Portuguese)",
                30 => "?? (Chinese)",
                31 => "Mercado y Economía",
                32 => "Hardware y Minería",
                33 => "Esquina Libre",
                34 => "Politics & Society",
                37 => "Wallet software",
                39 => "Beginners & Help",
                40 => "Mining support",
                41 => "Pools",
                42 => "Mining software (miners)",
                44 => "CPU/GPU Bitcoin mining hardware",
                45 => "Skandinavisk",
                47 => "Discussions générales et utilisation du Bitcoin",
                48 => "Mining et Hardware",
                49 => "Place de marché",
                50 => "Hors-sujet",
                51 => "Goods",
                52 => "Services",
                53 => "Currency exchange",
                54 => "Wiki - documentation et traduction",
                55 => "Xa???",
                56 => "Gambling",
                57 => "Speculation",
                59 => "Archival",
                60 => "Mining (Deutsch)",
                61 => "Trading und Spekulation",
                62 => "Anfänger und Hilfe",
                63 => "Projektentwicklung",
                64 => "Off-Topic (Deutsch)",
                65 => "Lending",
                66 => "?o?ep?",
                67 => "Altcoin Discussion",
                69 => "Economia & Mercado",
                70 => "Mineração em Geral",
                72 => "A???ep?a?????e ?p???o?a????",
                73 => "Auctions",
                74 => "Legal",
                76 => "Hardware",
                77 => "Press",
                78 => "Securities",
                79 => "Nederlands (Dutch)",
                80 => "Markt",
                81 => "Mining speculation",
                82 => "??? (Korean)",
                83 => "Scam Accusations",
                84 => "Service Announcements",
                85 => "Service Discussion",
                86 => "Meetups",
                87 => "Important Announcements",
                89 => "India",
                90 => "??e?",
                91 => "?o?????a",
                92 => "?op???a",
                94 => "Gokken/lotterijen",
                95 => "????? (Hebrew)",
                98 => "Electrum",
                99 => "MultiBit",
                101 => "Mercadillo",
                108 => "Român? (Romanian)",
                109 => "Anunturi importante",
                110 => "Offtopic",
                111 => "Market",
                112 => "Tutoriale",
                113 => "Bine ai venit!",
                114 => "Presa",
                115 => "Mining (Italiano)",
                116 => "Mining (Nederlands)",
                117 => "????",
                118 => "???",
                119 => "??",
                120 => "E??????? (Greek)",
                121 => "Mining (India)",
                122 => "Marketplace (India)",
                123 => "Regional Languages (India)",
                124 => "Press & News from India",
                125 => "Alt Coins (India)",
                126 => "Buyer/ Seller Reputations (India)",
                127 => "Off-Topic (India)",
                129 => "Reputation",
                130 => "Primeros pasos y ayuda",
                131 => "Primeiros Passos (Iniciantes)",
                132 => "Alt-Currencies (Italiano)",
                133 => "Türkçe (Turkish)",
                134 => "Brasil",
                135 => "Portugal",
                136 => "A?o??",
                139 => "Treffen",
                140 => "Presse",
                142 => "Polski",
                143 => "Beurzen",
                144 => "Raduni/Meeting (Italiano)",
                145 => "Off-Topic (Italiano)",
                146 => "??",
                147 => "Alt Coins (Nederlands)",
                148 => "Off-topic (Nederlands)",
                149 => "Altcoins (Français)",
                150 => "Meetings (Nederlands)",
                151 => "Altcoins (criptomonedas alternativas)",
                152 => "Altcoins (Deutsch)",
                153 => "Guide (Italiano)",
                155 => "Pazar Alan?",
                156 => "Madencilik",
                157 => "Alternatif Kripto-Paralar",
                158 => "Konu D???",
                159 => "Announcements (Altcoins)",
                160 => "Mining (Altcoins)",
                161 => "Marketplace (Altcoins)",
                162 => "Accuse scam/truffe",
                163 => "Tablica og?osze?",
                164 => "Alternatywne kryptowaluty",
                165 => "Crittografia e decentralizzazione",
                166 => "Minerit",
                167 => "New forum software",
                168 => "Bitcoin Wiki",
                169 => "Progetti",
                170 => "Mercato",
                174 => "Yeni Ba?layanlar & Yard?m",
                175 => "Trading - analisi e speculazione",
                179 => "Altcoins (E???????)",
                180 => "Bitcoin Haberleri",
                181 => "Criptomoedas Alternativas",
                182 => "???? Alt Coins (???)",
                183 => "Actualité et News",
                184 => "Vos sites et projets",
                185 => "Pa?o?a",
                186 => "Développement et technique",
                187 => "Économie et spéculation",
                188 => "Le Bitcoin et la loi",
                189 => "Ekonomi",
                190 => "Servisler",
                191 => "Bahasa Indonesia (Indonesian)",
                192 => "Altcoins (Bahasa Indonesia)",
                193 => "Jual Beli / Marketplace",
                194 => "Mining (Bahasa Indonesia)",
                195 => "Mining Discussion (E???????)",
                196 => "????",
                197 => "Service Announcements (Altcoins)",
                198 => "Service Discussion (Altcoins)",
                199 => "Pools (Altcoins)",
                201 => "Hrvatski (Croatian)",
                205 => "Discussioni avanzate e sviluppo",
                206 => "Desenvolvimento & Discussões Técnicas",
                208 => "Débutants",
                219 => "Pilipinas",
                220 => "Trgovina",
                221 => "Altcoins (Hrvatski)",
                224 => "Speculation (Altcoins)",
                228 => "Gambling discussion",
                229 => "Proje Geli?tirme",
                230 => "Bulu?malar",
                237 => "O??e?????",
                238 => "Bounties (Altcoins)",
                240 => "Tokens (Altcoins)",
                241 => "??????? (Arabic)",
                242 => "??????? ??????? (Altcoins)",
                243 => "Altcoins (Pilipinas)",
                250 => "Serious discussion",
                251 => "Ivory Tower",
                252 => "??? (Japanese)",
                253 => "????????? ? ????? ?????????",
                255 => "??????",
                259 => "Altcoins (Monede Alternative)",
                262 => "O?cy??e??e Bitcoin",
                266 => "???????",
                267 => "???????? ??????",
                268 => "Pamilihan",
                269 => "Marktplatz",
                271 => "????? ???????",
                272 => "Off-topic (Hrvatski)",
                274 => "Others (Pilipinas)",
                275 => "Nigeria (Naija)",
                276 => "Trading dan Spekulasi",
                277 => "Ekonomi - Politik - dan Budaya",
                278 => "Topik Lainnya",
                279 => "Politics and society (Naija)",
                280 => "Off-topic (Naija)"
            ];

            $stmt = $pdo->prepare("
                SELECT cm.board_id, COUNT(*) as vote_count, AVG(CAST(v.effective_value AS DECIMAL(10,4))) as avg_score
                FROM votes v
                JOIN content_metadata cm ON v.target_id = cm.topic_id
                WHERE v.vote_category = 'topic'
                GROUP BY cm.board_id
                ORDER BY avg_score DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $id = (int) $r['board_id'];
                $label = isset($boardNames[$id]) ? $boardNames[$id] : "Board " . $id;
                $data[] = [
                    'id' => $id,
                    'label' => $label,
                    'count' => (int) $r['vote_count'],
                    'avg' => round((float) $r['avg_score'], 2)
                ];
            }
            break;

        case 'topics':
            // Top Topics by Avg Score
            $stmt = $pdo->prepare("
                SELECT target_id, COUNT(*) as vote_count, AVG(CAST(effective_value AS DECIMAL(10,4))) as avg_score
                FROM votes
                WHERE vote_category = 'topic'
                GROUP BY target_id
                ORDER BY avg_score DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $data[] = [
                    'id' => (int) $r['target_id'],
                    'label' => "Topic " . $r['target_id'],
                    'count' => (int) $r['vote_count'],
                    'avg' => round((float) $r['avg_score'], 2)
                ];
            }
            break;

        case 'posts':
            // Top Posts by Avg Score WITH Author ID
            $stmt = $pdo->prepare("
                SELECT v.target_id, cm.author_id, COUNT(*) as vote_count, AVG(CAST(v.effective_value AS DECIMAL(10,4))) as avg_score
                FROM votes v
                LEFT JOIN content_metadata cm ON v.target_id = cm.post_id
                WHERE v.vote_category = 'post'
                GROUP BY v.target_id, cm.author_id
                ORDER BY avg_score DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $label = "Post " . $r['target_id'];
                if (!empty($r['author_id'])) {
                    $label .= " (" . $r['author_id'] . ")";
                }

                $data[] = [
                    'id' => (int) $r['target_id'],
                    'label' => $label,
                    'count' => (int) $r['vote_count'],
                    'avg' => round((float) $r['avg_score'], 2)
                ];
            }
            break;

        default:
            throw new Exception("Invalid action. Use members, boards, topics, or posts.");
    }

    echo json_encode(['ok' => true, 'data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
?>