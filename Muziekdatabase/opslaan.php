<?php
include('DatabaseConnector.php');
$database = new DatabaseConnector("muziekdatabase", "root", "root");

// ── Genres ophalen voor dropdown ──────────────────────────────────
$genres = $database->selectRows("SELECT id, naam FROM genre ORDER BY naam ASC");

// ── Formulier verwerken ───────────────────────────────────────────
$melding      = '';
$melding_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titel        = trim($_POST['titel']         ?? '');
    $artiest      = trim($_POST['artiest']        ?? '');
    $genre_id     = intval($_POST['genre_id']     ?? 0);
    $jaar         = intval($_POST['jaar']         ?? 0);
    $album        = trim($_POST['album']          ?? '');
    $link_youtube = trim($_POST['link_youtube']   ?? '');
    $link_chords  = trim($_POST['link_chords']    ?? '');
    $link_spotify = trim($_POST['link_spotify']   ?? '');

    if ($titel === '' || $artiest === '') {
        $melding      = 'Titel en artiest zijn verplicht.';
        $melding_type = 'fout';

    } else {
        // ── Duplicaat-check ───────────────────────────────────────
        $ct = addslashes($titel);
        $ca = addslashes($artiest);
        $bestaand = $database->selectRows(
            "SELECT id FROM nummer WHERE LOWER(titel) = LOWER('$ct') AND LOWER(artiest) = LOWER('$ca')"
        );

        if (!empty($bestaand)) {
            $melding      = '"' . htmlspecialchars($titel) . '" van ' . htmlspecialchars($artiest) . ' staat al in de database.';
            $melding_type = 'fout';

        } else {
            $tv  = addslashes($titel);
            $av  = addslashes($artiest);
            $alv = addslashes($album);
            $lyv = addslashes($link_youtube);
            $lcv = addslashes($link_chords);
            $lsv = addslashes($link_spotify);
            $jv  = ($jaar > 0) ? $jaar : 'NULL';

            $database->selectRows(
                "INSERT INTO nummer (titel, artiest, genre_id, jaar, album, link_youtube, link_chords, link_spotify)
                 VALUES ('$tv', '$av', $genre_id, $jv, '$alv', '$lyv', '$lcv', '$lsv')"
            );

            $melding      = '"' . htmlspecialchars($titel) . '" van ' . htmlspecialchars($artiest) . ' is opgeslagen!';
            $melding_type = 'succes';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nummer opslaan – Muziek Database</title>
    <link rel="stylesheet" href="muziek_style.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        .formulier {
            background: rgba(255,255,255,0.45);
            border-radius: 14px;
            padding: 28px 32px;
            max-width: 560px;
        }

        .formulier label {
            display: block;
            font-size: 0.85rem;
            font-weight: bold;
            color: #555;
            margin: 14px 0 5px;
        }

        .formulier input[type="text"],
        .formulier input[type="number"],
        .formulier select {
            width: 100%;
            padding: 9px 12px;
            font-size: 0.92rem;
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
            padding: 14px 16px;
            margin-top: 16px;
        }

        .link-sectie-titel {
            font-size: 0.85rem;
            font-weight: bold;
            color: #4a3080;
            margin-bottom: 10px;
        }

        .link-rij {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .link-rij:last-child { margin-bottom: 0; }

        .link-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: bold;
            color: white;
            white-space: nowrap;
            min-width: 80px;
            text-align: center;
        }

        .badge-youtube { background: #c0392b; }
        .badge-chords  { background: #27ae60; }
        .badge-spotify { background: #1db954; }

        .link-rij input {
            flex: 1;
            padding: 7px 10px;
            font-size: 0.88rem;
            border: 2px solid #c9a0e0;
            border-radius: 8px;
            font-family: Arial, sans-serif;
            background: white;
        }

        .link-rij input:focus { outline: none; border-color: #9060c0; }

        .formulier button[type="submit"] {
            margin-top: 20px;
            background: #c9a0e0;
            color: white;
            border: none;
            padding: 10px 28px;
            font-size: 0.95rem;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            font-family: Arial, sans-serif;
            transition: background 0.2s;
        }

        .formulier button[type="submit"]:hover { background: #b080d0; }

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

        .terug { display: inline-block; margin-top: 24px; }
    </style>
</head>
<body>

<div class="pagina">
    <header>
        <h1>MUZIEK DATABASE</h1>
        <p>Informatica VWO 6</p>
    </header>

    <div class="inhoud">
        <div class="tekst-links">
            <h2>Nummer opslaan</h2>
        </div>
    </div>

    <?php if ($melding !== ''): ?>
        <div class="melding <?= $melding_type ?>"><?= $melding ?></div>
    <?php endif; ?>

    <div class="formulier">
        <form method="POST" action="opslaan.php">

            <label>Titel <span style="color:#c0392b;">*</span></label>
            <input type="text" name="titel" placeholder="bijv. Blinding Lights" required
                   value="<?= ($melding_type === 'fout') ? htmlspecialchars($_POST['titel'] ?? '') : '' ?>">

            <label>Artiest <span style="color:#c0392b;">*</span></label>
            <input type="text" name="artiest" placeholder="bijv. The Weeknd" required
                   value="<?= ($melding_type === 'fout') ? htmlspecialchars($_POST['artiest'] ?? '') : '' ?>">

            <label>Genre</label>
            <select name="genre_id">
                <?php foreach ($genres as $g): ?>
                    <option value="<?= $g['id'] ?>"
                        <?= (isset($_POST['genre_id']) && $_POST['genre_id'] == $g['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['naam']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Jaar</label>
            <input type="number" name="jaar" placeholder="bijv. 2019" min="1900" max="2099"
                   value="<?= ($melding_type === 'fout') ? htmlspecialchars($_POST['jaar'] ?? '') : '' ?>">

            <label>Album</label>
            <input type="text" name="album" placeholder="bijv. After Hours"
                   value="<?= ($melding_type === 'fout') ? htmlspecialchars($_POST['album'] ?? '') : '' ?>">

            <!-- Links sectie -->
            <div class="link-sectie">
                <div class="link-sectie-titel">Links (allemaal optioneel)</div>

                <div class="link-rij">
                    <span class="link-badge badge-youtube">YouTube</span>
                    <input type="text" name="link_youtube" placeholder="https://youtube.com/..."
                           value="<?= ($melding_type === 'fout') ? htmlspecialchars($_POST['link_youtube'] ?? '') : '' ?>">
                </div>

                <div class="link-rij">
                    <span class="link-badge badge-chords">Chords</span>
                    <input type="text" name="link_chords" placeholder="https://ultimate-guitar.com/..."
                           value="<?= ($melding_type === 'fout') ? htmlspecialchars($_POST['link_chords'] ?? '') : '' ?>">
                </div>

                <div class="link-rij">
                    <span class="link-badge badge-spotify">Spotify</span>
                    <input type="text" name="link_spotify" placeholder="https://open.spotify.com/..."
                           value="<?= ($melding_type === 'fout') ? htmlspecialchars($_POST['link_spotify'] ?? '') : '' ?>">
                </div>
            </div>

            <button type="submit">Opslaan</button>
        </form>
    </div>

    <a href="index.php" class="knop terug">Terug</a>
</div>

<script>
    <?php if ($melding_type === 'succes'): ?>
    var einde = Date.now() + 3000;
    (function frame() {
        confetti({ particleCount: 6, angle: 60,  spread: 55, origin: { x: 0 }, colors: ['#c9a0e0','#e0b0f0','#f5d0ff','#ffffff'] });
        confetti({ particleCount: 6, angle: 120, spread: 55, origin: { x: 1 }, colors: ['#c9a0e0','#e0b0f0','#f5d0ff','#ffffff'] });
        if (Date.now() < einde) requestAnimationFrame(frame);
    }());
    <?php endif; ?>
</script>

</body>
</html>
