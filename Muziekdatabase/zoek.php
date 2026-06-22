<?php
include('DatabaseConnector.php');
$database = new DatabaseConnector("muziekdatabase", "root", "root");

// ── Genres ophalen ────────────────────────────────────────────────
$genres = $database->selectRows("SELECT id, naam FROM genre ORDER BY naam ASC");

// ── Sortering ─────────────────────────────────────────────────────
$toegestane_kolommen = ['titel', 'artiest', 'genre', 'jaar', 'album'];
$sort_kolom    = (isset($_GET['sort']) && in_array($_GET['sort'], $toegestane_kolommen)) ? $_GET['sort'] : 'titel';
$sort_richting = (isset($_GET['dir']) && $_GET['dir'] === 'desc') ? 'DESC' : 'ASC';
$sort_sql      = ($sort_kolom === 'genre') ? 'g.naam' : "n.$sort_kolom";

// ── Filters inlezen ───────────────────────────────────────────────
$zoek_titel   = trim($_GET['zoek_titel']   ?? '');
$zoek_artiest = trim($_GET['zoek_artiest'] ?? '');
$zoek_genre   = intval($_GET['zoek_genre'] ?? 0);
$zoek_jaar    = trim($_GET['zoek_jaar']    ?? '');
$zoek_link    = trim($_GET['zoek_link']    ?? '');  // 'youtube' | 'chords' | 'spotify' | 'een' | ''
$toon_alles   = isset($_GET['alles']);

$heeft_filters = ($zoek_titel !== '' || $zoek_artiest !== '' || $zoek_genre > 0 || $zoek_jaar !== '' || $zoek_link !== '');
$zoek_actief   = $heeft_filters || $toon_alles;

// ── Query bouwen ──────────────────────────────────────────────────
$resultaten = [];

if ($zoek_actief) {
    $conditions = [];

    if ($zoek_titel !== '') {
        $t = addslashes($zoek_titel);
        $conditions[] = "LOWER(n.titel) LIKE LOWER('%$t%')";
    }
    if ($zoek_artiest !== '') {
        $a = addslashes($zoek_artiest);
        $conditions[] = "LOWER(n.artiest) LIKE LOWER('%$a%')";
    }
    if ($zoek_genre > 0) {
        $conditions[] = "n.genre_id = $zoek_genre";
    }
    if ($zoek_jaar !== '') {
        $jv = intval($zoek_jaar);
        $conditions[] = "n.jaar = $jv";
    }

    // Link-type filter
    if ($zoek_link === 'youtube') {
        $conditions[] = "n.link_youtube IS NOT NULL AND n.link_youtube != ''";
    } elseif ($zoek_link === 'chords') {
        $conditions[] = "n.link_chords IS NOT NULL AND n.link_chords != ''";
    } elseif ($zoek_link === 'spotify') {
        $conditions[] = "n.link_spotify IS NOT NULL AND n.link_spotify != ''";
    } elseif ($zoek_link === 'een') {
        $conditions[] = "((n.link_youtube IS NOT NULL AND n.link_youtube != '') OR
                          (n.link_chords  IS NOT NULL AND n.link_chords  != '') OR
                          (n.link_spotify IS NOT NULL AND n.link_spotify != ''))";
    }

    $where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    // Relevantie-sortering bij titelzoekopdracht
    $relevantie_sql = '';
    if ($zoek_titel !== '') {
        $term_exact = addslashes($zoek_titel);
        $relevantie_sql = "CASE
            WHEN LOWER(n.titel)   = LOWER('$term_exact') THEN 0
            WHEN LOWER(n.artiest) = LOWER('$term_exact') THEN 1
            ELSE 2
        END,";
    }

    $resultaten = $database->selectRows(
        "SELECT n.id, n.titel, n.artiest, g.naam AS genre, n.jaar, n.album,
                n.link_youtube, n.link_chords, n.link_spotify
         FROM nummer n
         LEFT JOIN genre g ON n.genre_id = g.id
         $where
         ORDER BY $relevantie_sql $sort_sql $sort_richting"
    );
}

$aantal = count($resultaten);

// ── Sorteer-helpers ───────────────────────────────────────────────
$zoek_params = $_GET;

function sorteer_url($kolom) {
    global $sort_kolom, $sort_richting, $zoek_params;
    $params         = $zoek_params;
    $params['sort'] = $kolom;
    $params['dir']  = ($sort_kolom === $kolom && $sort_richting === 'ASC') ? 'desc' : 'asc';
    return 'zoek.php?' . http_build_query($params);
}

function sort_pijl($kolom) {
    global $sort_kolom, $sort_richting;
    if ($sort_kolom !== $kolom) return '<span class="pijl inactief">↕</span>';
    return '<span class="pijl actief">' . ($sort_richting === 'ASC' ? '↑' : '↓') . '</span>';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoek nummers – Muziek Database</title>
    <link rel="stylesheet" href="muziek_style.css">
    <style>
        .pagina-titel {
            font-size: 1.4rem;
            font-weight: bold;
            color: white;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
            margin-bottom: 16px;
        }

        .zoek-form {
            background: rgba(255,255,255,0.45);
            border-radius: 14px;
            padding: 22px 26px;
            margin-bottom: 24px;
        }

        .filter-rij {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            align-items: flex-end;
        }

        .filter-vak { display: flex; flex-direction: column; gap: 4px; }

        .filter-vak label {
            font-size: 0.82rem;
            font-weight: bold;
            color: #555;
        }

        .filter-vak input,
        .filter-vak select {
            padding: 8px 12px;
            border: 2px solid #c9a0e0;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: Arial, sans-serif;
            background: white;
        }

        .filter-vak input:focus,
        .filter-vak select:focus { outline: none; border-color: #9060c0; }

        .filter-vak.breed input { width: 180px; }
        .filter-vak.smal  input { width: 90px; }

        .filter-knoppen {
            display: flex;
            gap: 10px;
            margin-top: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .knop-zoek {
            background: #c9a0e0;
            color: white;
            border: none;
            padding: 9px 24px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: bold;
            cursor: pointer;
            font-family: Arial, sans-serif;
            transition: background 0.2s;
        }
        .knop-zoek:hover { background: #b080d0; }

        .knop-alles {
            background: rgba(201,160,224,0.3);
            color: #4a3080;
            border: 2px solid #c9a0e0;
            padding: 7px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: bold;
            cursor: pointer;
            font-family: Arial, sans-serif;
            text-decoration: none;
            transition: background 0.2s;
            display: inline-block;
        }
        .knop-alles:hover  { background: rgba(201,160,224,0.55); }
        .knop-alles.actief { background: rgba(201,160,224,0.55); }

        .wis-link {
            font-size: 0.85rem;
            color: #9060c0;
            text-decoration: none;
        }
        .wis-link:hover { text-decoration: underline; }

        .resultaat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .resultaat-aantal {
            font-size: 0.88rem;
            color: rgba(255,255,255,0.85);
        }

        .hint-zoek {
            background: rgba(255,255,255,0.35);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            color: rgba(255,255,255,0.8);
            font-size: 0.95rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255,255,255,0.45);
            border-radius: 12px;
            overflow: hidden;
        }

        th {
            background: rgba(201,160,224,0.5);
            padding: 11px 14px;
            text-align: left;
            font-size: 0.88rem;
            color: #4a3080;
            white-space: nowrap;
        }

        th a { color: #4a3080; text-decoration: none; font-weight: bold; }
        th a:hover { text-decoration: underline; }

        .pijl { margin-left: 4px; font-size: 0.8rem; }
        .pijl.actief   { color: #7a30c0; }
        .pijl.inactief { color: #bbb; }

        td {
            padding: 10px 14px;
            font-size: 0.88rem;
            color: #2a1a44;
            border-top: 1px solid rgba(201,160,224,0.25);
        }

        tr:hover td { background: rgba(201,160,224,0.12); }

        .match-badge {
            display: inline-block;
            background: #c9a0e0;
            color: white;
            font-size: 0.68rem;
            font-weight: bold;
            padding: 1px 7px;
            border-radius: 20px;
            margin-left: 6px;
            vertical-align: middle;
        }

        /* Link-knoppen */
        .links-cel { display: flex; gap: 5px; flex-wrap: wrap; }

        .link-knop {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: bold;
            text-decoration: none;
            color: white;
            transition: opacity 0.15s;
            white-space: nowrap;
        }
        .link-knop:hover { opacity: 0.8; }

        .link-yt { background: #c0392b; }
        .link-ch { background: #27ae60; }
        .link-sp { background: #1db954; }

        .geen-resultaten {
            text-align: center;
            padding: 30px;
            color: #888;
            font-style: italic;
            background: rgba(255,255,255,0.45);
            border-radius: 12px;
        }

        .terug { display: inline-block; margin-top: 30px; }
    </style>
</head>
<body>

<div class="pagina">
    <header>
        <h1>MUZIEK DATABASE</h1>
        <p>Informatica VWO 6</p>
    </header>

    <div class="pagina-titel">Zoek nummers</div>

    <!-- Zoekformulier -->
    <form method="GET" action="zoek.php" class="zoek-form">
        <div class="filter-rij">
            <div class="filter-vak breed">
                <label>Titel</label>
                <input type="text" name="zoek_titel" value="<?= htmlspecialchars($zoek_titel) ?>" placeholder="bijv. Blinding Lights">
            </div>
            <div class="filter-vak breed">
                <label>Artiest</label>
                <input type="text" name="zoek_artiest" value="<?= htmlspecialchars($zoek_artiest) ?>" placeholder="bijv. The Weeknd">
            </div>
            <div class="filter-vak">
                <label>Genre</label>
                <select name="zoek_genre">
                    <option value="0">— Alle genres —</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= ($zoek_genre == $g['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['naam']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-vak smal">
                <label>Jaar</label>
                <input type="number" name="zoek_jaar" value="<?= htmlspecialchars($zoek_jaar) ?>" placeholder="2019" min="1900" max="2099">
            </div>
            <div class="filter-vak">
                <label>Soort link</label>
                <select name="zoek_link">
                    <option value=""        <?= $zoek_link === ''       ? 'selected' : '' ?>>Alle</option>
                    <option value="een"     <?= $zoek_link === 'een'    ? 'selected' : '' ?>>Heeft link</option>
                    <option value="youtube" <?= $zoek_link === 'youtube'? 'selected' : '' ?>>YouTube</option>
                    <option value="chords"  <?= $zoek_link === 'chords' ? 'selected' : '' ?>>Chords</option>
                    <option value="spotify" <?= $zoek_link === 'spotify'? 'selected' : '' ?>>Spotify</option>
                </select>
            </div>
        </div>
        <div class="filter-knoppen">
            <button type="submit" class="knop-zoek">Zoeken</button>
            <a href="zoek.php?alles=1&sort=<?= htmlspecialchars($sort_kolom) ?>&dir=<?= strtolower($sort_richting) ?>"
               class="knop-alles <?= $toon_alles ? 'actief' : '' ?>">Toon alles</a>
            <?php if ($heeft_filters): ?>
                <a href="zoek.php" class="wis-link">Filters wissen ✕</a>
            <?php endif; ?>
        </div>
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_kolom) ?>">
        <input type="hidden" name="dir"  value="<?= strtolower($sort_richting) ?>">
    </form>

    <!-- Resultaten -->
    <?php if (!$zoek_actief): ?>
        <div class="hint-zoek">Vul een zoekterm in of klik op "Toon alles".</div>

    <?php elseif (empty($resultaten)): ?>
        <div class="geen-resultaten">Geen nummers gevonden met deze filters.</div>

    <?php else: ?>
        <div class="resultaat-header">
            <span class="resultaat-aantal">
                <?= $aantal ?> nummer<?= $aantal !== 1 ? 's' : '' ?> gevonden
                <?php if ($zoek_titel !== '' && $aantal > 1): ?>
                    <span style="color:rgba(255,255,255,0.6); font-size:0.8rem;">— exacte overeenkomsten staan bovenaan</span>
                <?php endif; ?>
            </span>
        </div>

        <table>
            <thead>
                <tr>
                    <th><a href="<?= sorteer_url('titel') ?>">Titel <?= sort_pijl('titel') ?></a></th>
                    <th><a href="<?= sorteer_url('artiest') ?>">Artiest <?= sort_pijl('artiest') ?></a></th>
                    <th><a href="<?= sorteer_url('genre') ?>">Genre <?= sort_pijl('genre') ?></a></th>
                    <th><a href="<?= sorteer_url('jaar') ?>">Jaar <?= sort_pijl('jaar') ?></a></th>
                    <th><a href="<?= sorteer_url('album') ?>">Album <?= sort_pijl('album') ?></a></th>
                    <th>Links</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultaten as $nummer): ?>
                    <?php
                        $is_exact = ($zoek_titel !== '' && strtolower($nummer['titel']) === strtolower($zoek_titel));
                    ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($nummer['titel']) ?>
                            <?php if ($is_exact): ?>
                                <span class="match-badge">exacte match</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($nummer['artiest']) ?></td>
                        <td><?= htmlspecialchars($nummer['genre'] ?? '—') ?></td>
                        <td><?= $nummer['jaar'] ?: '—' ?></td>
                        <td><?= htmlspecialchars($nummer['album'] ?? '—') ?></td>
                        <td>
                            <div class="links-cel">
                                <?php if (!empty($nummer['link_youtube'])): ?>
                                    <a href="<?= htmlspecialchars($nummer['link_youtube']) ?>"
                                       target="_blank" class="link-knop link-yt">YT</a>
                                <?php endif; ?>
                                <?php if (!empty($nummer['link_chords'])): ?>
                                    <a href="<?= htmlspecialchars($nummer['link_chords']) ?>"
                                       target="_blank" class="link-knop link-ch">Chords</a>
                                <?php endif; ?>
                                <?php if (!empty($nummer['link_spotify'])): ?>
                                    <a href="<?= htmlspecialchars($nummer['link_spotify']) ?>"
                                       target="_blank" class="link-knop link-sp">Spotify</a>
                                <?php endif; ?>
                                <?php if (empty($nummer['link_youtube']) && empty($nummer['link_chords']) && empty($nummer['link_spotify'])): ?>
                                    <span style="color:#bbb;">—</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="index.php" class="knop terug">Terug</a>

</div>

</body>
</html>
