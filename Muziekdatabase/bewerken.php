<?php
include('DatabaseConnector.php');
$database = new DatabaseConnector("muziekdatabase", "root", "root");

$genres = $database->selectRows("SELECT id, naam FROM genre ORDER BY naam ASC");

$melding      = '';
$melding_type = '';
$bewerk_nummer = null;

// ── POST-acties ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'verwijder') {
        $id = intval($_POST['id']);
        $database->selectRows("DELETE FROM nummer WHERE id = $id");
        $melding      = 'Nummer verwijderd.';
        $melding_type = 'succes';

    } elseif ($action === 'bewerken') {
        $id      = intval($_POST['id']);
        $titel   = trim($_POST['titel']        ?? '');
        $artiest = trim($_POST['artiest']       ?? '');
        $genre_id= intval($_POST['genre_id']   ?? 0);
        $jaar    = intval($_POST['jaar']        ?? 0);
        $album   = trim($_POST['album']         ?? '');
        $lyv     = addslashes(trim($_POST['link_youtube'] ?? ''));
        $lcv     = addslashes(trim($_POST['link_chords']  ?? ''));
        $lsv     = addslashes(trim($_POST['link_spotify'] ?? ''));

        if ($titel === '' || $artiest === '') {
            $melding      = 'Titel en artiest zijn verplicht.';
            $melding_type = 'fout';
        } else {
            $tv  = addslashes($titel);
            $av  = addslashes($artiest);
            $alv = addslashes($album);
            $jv  = ($jaar > 0) ? $jaar : 'NULL';

            $database->selectRows(
                "UPDATE nummer SET titel='$tv', artiest='$av', genre_id=$genre_id,
                 jaar=$jv, album='$alv',
                 link_youtube='$lyv', link_chords='$lcv', link_spotify='$lsv'
                 WHERE id=$id"
            );
            $melding      = '"' . htmlspecialchars($titel) . '" is bijgewerkt!';
            $melding_type = 'succes';
        }
    }
}

// ── Nummer ophalen voor bewerken ──────────────────────────────────
$bewerk_id = intval($_GET['bewerk'] ?? 0);
if ($bewerk_id > 0 && $melding_type !== 'fout') {
    $resultaat = $database->selectRows("SELECT * FROM nummer WHERE id = $bewerk_id");
    if (!empty($resultaat)) $bewerk_nummer = $resultaat[0];
}

// ── Zoeken ────────────────────────────────────────────────────────
$zoek = trim($_GET['zoek'] ?? '');
$zoek_resultaten = [];
if ($zoek !== '') {
    $z = addslashes($zoek);
    $zoek_resultaten = $database->selectRows(
        "SELECT n.id, n.titel, n.artiest, g.naam AS genre, n.jaar
         FROM nummer n LEFT JOIN genre g ON n.genre_id = g.id
         WHERE LOWER(n.titel) LIKE LOWER('%$z%') OR LOWER(n.artiest) LIKE LOWER('%$z%')
         ORDER BY n.titel ASC
         LIMIT 50"
    );
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bewerken – Muziek Database</title>
    <link rel="stylesheet" href="muziek_style.css">
    <style>
        /* ── Tabs ──────────────────────────────────────────────── */
        .tabs {
            display: flex;
            gap: 0;
            margin-bottom: 24px;
            border-bottom: 2px solid #c9a0e0;
            max-width: 560px;
        }

        .tab {
            padding: 10px 26px;
            font-size: 0.92rem;
            font-weight: bold;
            text-decoration: none;
            color: #9060c0;
            border-radius: 8px 8px 0 0;
            background: rgba(201,160,224,0.15);
            border: 2px solid transparent;
            border-bottom: none;
            transition: background 0.2s;
        }

        .tab:hover  { background: rgba(201,160,224,0.3); }
        .tab.actief {
            background: white;
            color: #4a3080;
            border-color: #c9a0e0;
            border-bottom: 2px solid white;
            margin-bottom: -2px;
        }

        /* ── Zoekbalk ──────────────────────────────────────────── */
        .zoek-balk {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            max-width: 560px;
        }

        .zoek-balk input {
            flex: 1;
            padding: 9px 14px;
            font-size: 0.92rem;
            border: 2px solid #c9a0e0;
            border-radius: 8px;
            font-family: Arial, sans-serif;
            background: white;
        }

        .zoek-balk input:focus { outline: none; border-color: #9060c0; }

        .zoek-balk button {
            background: #c9a0e0;
            color: white;
            border: none;
            padding: 9px 22px;
            font-size: 0.92rem;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            font-family: Arial, sans-serif;
        }

        .zoek-balk button:hover { background: #b080d0; }

        /* ── Zoekresultaten ────────────────────────────────────── */
        .resultaten-tabel {
            width: 100%;
            max-width: 700px;
            border-collapse: collapse;
            margin-bottom: 24px;
            background: rgba(255,255,255,0.45);
            border-radius: 12px;
            overflow: hidden;
            font-size: 0.88rem;
        }

        .resultaten-tabel th {
            background: rgba(201,160,224,0.4);
            padding: 10px 14px;
            text-align: left;
            color: #4a3080;
            font-weight: bold;
        }

        .resultaten-tabel td {
            padding: 9px 14px;
            border-top: 1px solid rgba(201,160,224,0.2);
            color: #2a1a44;
        }

        .resultaten-tabel tr:hover td { background: rgba(201,160,224,0.12); }

        .btn-bewerk {
            background: #c9a0e0;
            color: white;
            border: none;
            padding: 4px 14px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: bold;
            cursor: pointer;
            font-family: Arial, sans-serif;
            text-decoration: none;
            display: inline-block;
        }
        .btn-bewerk:hover { background: #b080d0; }

        .btn-verwijder {
            background: rgba(192,57,43,0.15);
            color: #c0392b;
            border: 1px solid rgba(192,57,43,0.3);
            padding: 4px 14px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: bold;
            cursor: pointer;
            font-family: Arial, sans-serif;
        }
        .btn-verwijder:hover { background: rgba(192,57,43,0.3); }

        /* ── Bewerkformulier ────────────────────────────────────── */
        .formulier {
            background: rgba(255,255,255,0.45);
            border-radius: 14px;
            padding: 24px 28px;
            max-width: 560px;
            margin-bottom: 20px;
        }

        .formulier-titel {
            font-size: 1rem;
            font-weight: bold;
            color: #4a3080;
            margin-bottom: 16px;
        }

        .formulier label {
            display: block;
            font-size: 0.83rem;
            font-weight: bold;
            color: #555;
            margin: 12px 0 4px;
        }

        .formulier input[type="text"],
        .formulier input[type="number"],
        .formulier select {
            width: 100%;
            padding: 9px 12px;
            font-size: 0.9rem;
            border: 2px solid #c9a0e0;
            border-radius: 8px;
            font-family: Arial, sans-serif;
            background: white;
            box-sizing: border-box;
        }

        .formulier input:focus,
        .formulier select:focus { outline: none; border-color: #9060c0; }

        .link-sectie {
            background: rgba(201,160,224,0.12);
            border-radius: 10px;
            padding: 12px 14px;
            margin-top: 14px;
        }

        .link-sectie-titel {
            font-size: 0.83rem;
            font-weight: bold;
            color: #4a3080;
            margin-bottom: 10px;
        }

        .link-rij {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .link-rij:last-child { margin-bottom: 0; }

        .link-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: bold;
            color: white;
            min-width: 72px;
            text-align: center;
            white-space: nowrap;
        }

        .badge-youtube { background: #c0392b; }
        .badge-chords  { background: #27ae60; }
        .badge-spotify { background: #1db954; }

        .link-rij input {
            flex: 1;
            padding: 7px 10px;
            font-size: 0.86rem;
            border: 2px solid #c9a0e0;
            border-radius: 8px;
            font-family: Arial, sans-serif;
            background: white;
        }
        .link-rij input:focus { outline: none; border-color: #9060c0; }

        .formulier-knoppen {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }

        .btn-opslaan {
            background: #c9a0e0;
            color: white;
            border: none;
            padding: 10px 26px;
            font-size: 0.95rem;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            font-family: Arial, sans-serif;
            transition: background 0.2s;
        }
        .btn-opslaan:hover { background: #b080d0; }

        .btn-annuleer {
            background: rgba(160,160,160,0.2);
            color: #555;
            border: 2px solid #ccc;
            padding: 9px 20px;
            font-size: 0.9rem;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            font-family: Arial, sans-serif;
            text-decoration: none;
        }
        .btn-annuleer:hover { background: rgba(160,160,160,0.35); }

        /* ── Meldingen ─────────────────────────────────────────── */
        .melding {
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 0.93rem;
            font-weight: bold;
            margin-bottom: 20px;
            max-width: 560px;
        }
        .melding.succes { background: rgba(100,200,120,0.25); color: #1a6030; border: 1px solid rgba(100,200,120,0.5); }
        .melding.fout   { background: rgba(220,80,80,0.18);  color: #721c24; border: 1px solid rgba(220,80,80,0.35); }

        .geen-resultaten { color: #888; font-style: italic; font-size: 0.9rem; margin-bottom: 16px; }

        .terug { display: inline-block; margin-top: 24px; }
    </style>
</head>
<body>
<div class="pagina">
    <header>
        <h1>MUZIEK DATABASE</h1>
        <p>Informatica VWO 6</p>
    </header>

    <div class="inhoud" style="flex-direction:column; align-items:flex-start; overflow-y:auto;">

        <!-- Tabs -->
        <div class="tabs">
            <a href="opslaan.php" class="tab">Nieuw nummer</a>
            <a href="bewerken.php" class="tab actief">Bewerk / verwijder</a>
        </div>

        <!-- Melding -->
        <?php if ($melding !== ''): ?>
            <div class="melding <?= $melding_type ?>"><?= $melding ?></div>
        <?php endif; ?>

        <!-- Bewerkformulier (als nummer geselecteerd is) -->
        <?php if ($bewerk_nummer !== null): ?>
            <div class="formulier">
                <div class="formulier-titel">Nummer bewerken</div>
                <form method="POST" action="bewerken.php">
                    <input type="hidden" name="action" value="bewerken">
                    <input type="hidden" name="id" value="<?= $bewerk_nummer['id'] ?>">

                    <label>Titel <span style="color:#c0392b;">*</span></label>
                    <input type="text" name="titel" value="<?= htmlspecialchars($bewerk_nummer['titel']) ?>" required>

                    <label>Artiest <span style="color:#c0392b;">*</span></label>
                    <input type="text" name="artiest" value="<?= htmlspecialchars($bewerk_nummer['artiest']) ?>" required>

                    <label>Genre</label>
                    <select name="genre_id">
                        <?php foreach ($genres as $g): ?>
                            <option value="<?= $g['id'] ?>"
                                <?= ($g['id'] == $bewerk_nummer['genre_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['naam']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Jaar</label>
                    <input type="number" name="jaar" value="<?= htmlspecialchars($bewerk_nummer['jaar'] ?? '') ?>" min="1900" max="2099">

                    <label>Album</label>
                    <input type="text" name="album" value="<?= htmlspecialchars($bewerk_nummer['album'] ?? '') ?>">

                    <div class="link-sectie">
                        <div class="link-sectie-titel">Links</div>
                        <div class="link-rij">
                            <span class="link-badge badge-youtube">YouTube</span>
                            <input type="text" name="link_youtube" value="<?= htmlspecialchars($bewerk_nummer['link_youtube'] ?? '') ?>">
                        </div>
                        <div class="link-rij">
                            <span class="link-badge badge-chords">Chords</span>
                            <input type="text" name="link_chords" value="<?= htmlspecialchars($bewerk_nummer['link_chords'] ?? '') ?>">
                        </div>
                        <div class="link-rij">
                            <span class="link-badge badge-spotify">Spotify</span>
                            <input type="text" name="link_spotify" value="<?= htmlspecialchars($bewerk_nummer['link_spotify'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="formulier-knoppen">
                        <button type="submit" class="btn-opslaan">Opslaan</button>
                        <a href="bewerken.php<?= $zoek !== '' ? '?zoek='.urlencode($zoek) : '' ?>"
                           class="btn-annuleer">Annuleren</a>
                    </div>
                </form>
            </div>

        <?php else: ?>

            <!-- Zoekbalk -->
            <form method="GET" action="bewerken.php" class="zoek-balk">
                <input type="text" name="zoek" placeholder="Zoek op titel of artiest..."
                       value="<?= htmlspecialchars($zoek) ?>">
                <button type="submit">Zoeken</button>
            </form>

            <!-- Zoekresultaten -->
            <?php if ($zoek !== '' && empty($zoek_resultaten)): ?>
                <div class="geen-resultaten">Geen nummers gevonden voor "<?= htmlspecialchars($zoek) ?>".</div>

            <?php elseif (!empty($zoek_resultaten)): ?>
                <table class="resultaten-tabel">
                    <thead>
                        <tr>
                            <th>Titel</th>
                            <th>Artiest</th>
                            <th>Genre</th>
                            <th>Jaar</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($zoek_resultaten as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['titel']) ?></td>
                                <td><?= htmlspecialchars($r['artiest']) ?></td>
                                <td><?= htmlspecialchars($r['genre'] ?? '—') ?></td>
                                <td><?= $r['jaar'] ?: '—' ?></td>
                                <td style="white-space:nowrap; display:flex; gap:6px;">
                                    <a href="bewerken.php?bewerk=<?= $r['id'] ?>&zoek=<?= urlencode($zoek) ?>"
                                       class="btn-bewerk">Bewerk</a>

                                    <form method="POST" action="bewerken.php"
                                          onsubmit="return confirm('Weet je zeker dat je <?= addslashes(htmlspecialchars($r['titel'])) ?> wilt verwijderen?')">
                                        <input type="hidden" name="action" value="verwijder">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="btn-verwijder">Verwijder</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php endif; ?>

        <a href="index.php" class="knop terug">Terug</a>
    </div>
</div>
</body>
</html>
