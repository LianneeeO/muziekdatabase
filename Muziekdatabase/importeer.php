<?php
ini_set('max_execution_time', 300); // 5 minuten voor grote bestanden

include('DatabaseConnector.php');
$database = new DatabaseConnector("muziekdatabase", "root", "root");

$melding      = '';
$melding_type = '';
$details      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvbestand'])) {
    $bestand = $_FILES['csvbestand'];

    if ($bestand['error'] !== UPLOAD_ERR_OK) {
        $melding      = 'Er ging iets mis bij het uploaden.';
        $melding_type = 'fout';

    } else {
        $inhoud = file_get_contents($bestand['tmp_name']);
        $regels = explode("\n", str_replace("\r\n", "\n", str_replace("\r", "\n", $inhoud)));
        $regels = array_filter($regels, fn($r) => trim($r) !== '');
        $regels = array_values($regels);

        if (empty($regels)) {
            $melding      = 'Het bestand is leeg.';
            $melding_type = 'fout';
        } else {
            // ── Scheidingsteken detecteren ─────────────────────────
            $separator = (substr_count($regels[0], ';') > substr_count($regels[0], ',')) ? ';' : ',';

            // ── Kolomkoppen lezen en mappen ────────────────────────
            $headers = str_getcsv($regels[0], $separator);
            $headers = array_map('strtolower', array_map('trim', $headers));

            // Mogelijke kolomnamen per veld (eigen formaat én Spotify-formaat)
            // array_search geeft false terug als niet gevonden, dus ?? werkt hier niet —
            // we gebruiken een helperfunctie die false behandelt als "niet gevonden"
            function vind_kolom($headers, $namen) {
                foreach ($namen as $naam) {
                    $index = array_search($naam, $headers);
                    if ($index !== false) return $index;
                }
                return false;
            }

            $kolom_titel   = vind_kolom($headers, ['titel', 'track_name', 'name']);
            $kolom_artiest = vind_kolom($headers, ['artiest', 'track_artist', 'artist']);
            $kolom_genre   = vind_kolom($headers, ['genre', 'playlist_genre']);
            $kolom_jaar    = vind_kolom($headers, ['jaar', 'track_album_release_date', 'year']);
            $kolom_album   = vind_kolom($headers, ['album', 'track_album_name']);
            $kolom_link    = vind_kolom($headers, ['link', 'url']);

            if ($kolom_titel === false || $kolom_artiest === false) {
                $melding      = 'Kolommen voor titel en artiest niet gevonden. Controleer het bestand.';
                $melding_type = 'fout';
            } else {
                // ── Genres uit database ophalen ────────────────────
                $genre_rijen = $database->selectRows("SELECT id, naam FROM genre");
                $genre_map   = [];
                $fallback_id = 1;
                foreach ($genre_rijen as $g) {
                    $genre_map[strtolower($g['naam'])] = $g['id'];
                    $fallback_id = $g['id']; // laatste als fallback
                }

                // Limiet instellen
                $limiet = isset($_POST['limiet']) ? intval($_POST['limiet']) : 1000;
                $limiet = min($limiet, 5000); // maximaal 5000 per keer

                $ingevoegd    = 0;
                $overgeslagen = 0;
                $dubbel       = 0;
                $fout_regels  = [];

                // ── Bestaande nummers ophalen voor duplicaat-check ─
                $bestaande_rijen = $database->selectRows("SELECT LOWER(titel) AS t, LOWER(artiest) AS a FROM nummer");
                $bestaande = [];
                foreach ($bestaande_rijen as $r) {
                    $bestaande[$r['t'] . '|||' . $r['a']] = true;
                }

                // ── Rijen verwerken ────────────────────────────────
                $verwerkt = 0;
                for ($i = 1; $i < count($regels) && $ingevoegd < $limiet; $i++) {
                    $velden = str_getcsv($regels[$i], $separator);
                    if (count($velden) < 2) continue;

                    $titel   = trim($velden[$kolom_titel]   ?? '');
                    $artiest = trim($velden[$kolom_artiest] ?? '');

                    if ($titel === '' || $artiest === '') {
                        $overgeslagen++;
                        continue;
                    }

                    // Duplicaat-check
                    $sleutel = strtolower($titel) . '|||' . strtolower($artiest);
                    if (isset($bestaande[$sleutel])) {
                        $dubbel++;
                        continue;
                    }

                    // Genre
                    $genre_naam = strtolower(trim($velden[$kolom_genre] ?? ''));
                    $genre_id   = $genre_map[$genre_naam] ?? $fallback_id;

                    // Jaar: haal eerste 4 tekens op (werkt voor "2019" én "2019-06-14")
                    $jaar_raw = trim($velden[$kolom_jaar] ?? '');
                    $jaar     = (preg_match('/(\d{4})/', $jaar_raw, $m)) ? intval($m[1]) : 'NULL';
                    if ($jaar !== 'NULL' && ($jaar < 1900 || $jaar > 2099)) $jaar = 'NULL';

                    // Album en link
                    $album = addslashes(trim($velden[$kolom_album] ?? ''));
                    $link  = addslashes(trim($velden[$kolom_link]  ?? ''));

                    $titel_veilig   = addslashes($titel);
                    $artiest_veilig = addslashes($artiest);

                    $database->selectRows(
                        "INSERT INTO nummer (titel, artiest, genre_id, jaar, album, link)
                         VALUES ('$titel_veilig', '$artiest_veilig', $genre_id, $jaar, '$album', '$link')"
                    );

                    // Toevoegen aan bestaande zodat dubbelen binnen dit bestand ook worden gepakt
                    $bestaande[$sleutel] = true;
                    $ingevoegd++;
                }

                $melding_type = 'succes';
                $melding      = "$ingevoegd nummers succesvol geïmporteerd.";
                $details      = [];
                if ($dubbel > 0)       $details[] = "$dubbel dubbelen overgeslagen (stonden al in de database)";
                if ($overgeslagen > 0) $details[] = "$overgeslagen regels overgeslagen (geen titel of artiest)";
                if ($ingevoegd >= $limiet) $details[] = "Limiet van $limiet bereikt — importeer opnieuw voor meer";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importeer nummers – Muziek Database</title>
    <link rel="stylesheet" href="muziek_style.css">
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

        .formulier input[type="file"],
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

        .formulier input:focus { outline: none; border-color: #9060c0; }

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
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 0.93rem;
            font-weight: bold;
            margin-bottom: 20px;
            max-width: 560px;
        }
        .melding.succes { background: rgba(100,200,120,0.25); color: #1a6030; border: 1px solid rgba(100,200,120,0.5); }
        .melding.fout   { background: rgba(220,80,80,0.18);  color: #721c24; border: 1px solid rgba(220,80,80,0.35); }

        .detail-lijst {
            margin-top: 8px;
            font-size: 0.85rem;
            font-weight: normal;
            list-style: none;
            padding: 0;
        }
        .detail-lijst li::before { content: "→ "; }

        .uitleg-blok {
            background: rgba(255,255,255,0.35);
            border-radius: 10px;
            padding: 16px 20px;
            margin-top: 24px;
            max-width: 560px;
            font-size: 0.88rem;
            color: #333;
            line-height: 1.7;
        }

        .uitleg-blok strong { color: #4a3080; }

        .uitleg-blok code {
            background: rgba(201,160,224,0.25);
            padding: 1px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }

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
            <h2>Importeer nummers</h2>
        </div>
    </div>

    <?php if ($melding !== ''): ?>
        <div class="melding <?= $melding_type ?>">
            <?= $melding ?>
            <?php if (!empty($details)): ?>
                <ul class="detail-lijst">
                    <?php foreach ($details as $d): ?>
                        <li><?= htmlspecialchars($d) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="formulier">
        <form method="POST" action="importeer.php" enctype="multipart/form-data">

            <label>CSV-bestand (.csv of .txt)</label>
            <input type="file" name="csvbestand" accept=".csv,.txt" required>

            <label>Hoeveel nummers importeren? (max. 5000 per keer)</label>
            <input type="number" name="limiet" value="1000" min="1" max="5000">

            <button type="submit">Importeren</button>
        </form>
    </div>

    <div class="uitleg-blok">
        <strong>Ondersteunde formaten:</strong><br>
        Het bestand herkent automatisch de kolomnamen. Werkt met:<br><br>
        <strong>Eigen formaat:</strong> <code>titel, artiest, genre, jaar, album, link</code><br>
        <strong>Spotify-formaat:</strong> <code>track_name, track_artist, playlist_genre, track_album_release_date, track_album_name</code><br><br>
        Komma en puntkomma worden allebei herkend als scheidingsteken. Dubbele nummers worden automatisch overgeslagen.
    </div>

    <a href="index.php" class="knop terug">Terug</a>
</div>

</body>
</html>
