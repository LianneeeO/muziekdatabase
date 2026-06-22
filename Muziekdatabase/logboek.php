<?php
include('DatabaseConnector.php');
$database = new DatabaseConnector("muziekdatabase", "root", "root");

// ── Acties verwerken ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actie = $_POST['actie'] ?? '';
    $id    = intval($_POST['id'] ?? 0);

    if ($actie === 'verwijder' && $id > 0) {
        $database->selectRows("DELETE FROM logboek WHERE id = $id");

    } elseif ($actie === 'bewerk' && $id > 0) {
        $tekst    = addslashes(trim($_POST['tekst']    ?? ''));
        $titel    = addslashes(trim($_POST['titel']    ?? ''));
        $domeinen = addslashes(trim($_POST['domeinen'] ?? ''));
        $database->selectRows("UPDATE logboek SET tekst='$tekst', titel='$titel', domeinen='$domeinen' WHERE id=$id");

    } elseif ($actie === 'toevoegen_systeem') {
        $tekst    = addslashes(trim($_POST['tekst']    ?? ''));
        $titel    = addslashes(trim($_POST['titel']    ?? ''));
        $domeinen = addslashes(trim($_POST['domeinen'] ?? ''));
        if ($titel !== '' && $tekst !== '') {
            $database->selectRows("INSERT INTO logboek (soort, titel, domeinen, tekst) VALUES ('systeem', '$titel', '$domeinen', '$tekst')");
        }

    } elseif ($actie === 'toevoegen') {
        $tekst = addslashes(trim($_POST['tekst'] ?? ''));
        if ($tekst !== '') {
            $database->selectRows("INSERT INTO logboek (soort, tekst) VALUES ('persoonlijk', '$tekst')");
        }
    }

    header('Location: logboek.php');
    exit;
}

// ── Auto-seed systeem-blokken als ze nog niet bestaan ─────────────
$check = $database->selectRows("SELECT COUNT(*) AS n FROM logboek WHERE soort = 'systeem'");
if ($check[0]['n'] == 0) {
    $blokken = [
        [
            'titel'    => '1. Database aangemaakt in PHPMyAdmin',
            'domeinen' => 'C2, C3, H1',
            'tekst'    => "<p>De database heet <code>muziekdatabase</code> en draait lokaal via MAMP op <code>localhost</code>. Er zijn drie tabellen aangemaakt:</p><pre>genre   → id, naam\nnummer  → id, titel, artiest, genre_id, jaar, album, link\nlogboek → id, soort, titel, domeinen, tekst, datum</pre><p>De tabel <code>genre</code> bevat 8 standaardgenres. De tabel <code>nummer</code> is gekoppeld aan <code>genre</code> via een <em>foreign key</em> (H1: informatiemodellering). Later zijn kolommen toegevoegd via <code>ALTER TABLE</code>.</p><p><strong>Domein H2:</strong> Dit project gebruikt het <em>relationele</em> paradigma (SQL/MySQL): data in tabellen met vaste kolommen en relaties via foreign keys. Een alternatief is het <em>document-gebaseerde</em> paradigma (JSON/MongoDB). Het relationele paradigma is hier beter omdat genres maar eenmalig hoeven te worden opgeslagen.</p>"
        ],
        [
            'titel'    => '2. DatabaseConnector.php',
            'domeinen' => 'D1, D2',
            'tekst'    => "<p>Verbinding met de database via <strong>PDO</strong> (PHP Data Objects). Verbinding aanmaken:</p><pre>\$database = new DatabaseConnector(\"muziekdatabase\", \"root\", \"root\");</pre><p>Daarna gebruik je <code>selectRows()</code> voor alle SQL-opdrachten. Dit werkt voor SELECT (ophalen) en INSERT (opslaan).</p><p><strong>Domein N - SQL-injectie:</strong> De huidige code bouwt queries als tekststrings. De veilige oplossing zijn prepared statements met parameters. In dit project gebruiken we <code>addslashes()</code> en <code>intval()</code> als basisbeveiliging.</p>"
        ],
        [
            'titel'    => '3. muziek_style.css — de opmaak',
            'domeinen' => 'O3, P2',
            'tekst'    => "<p>De achtergrond is een paars-roze verloop en de pagina heeft een wit kader:</p><pre>background: linear-gradient(135deg, #e8d8f0 0%, #f5ddf5 50%, #fce8f8 100%);\nborder: 3px solid white;</pre><p><strong>Domein O3/P2 - ontwerpkeuzes:</strong> Alle knoppen hebben dezelfde kleur voor consistentie. Hover-effecten geven visuele feedback. Arial is gekozen voor leesbaarheid.</p>"
        ],
        [
            'titel'    => '4. index.php — de hoofdpagina',
            'domeinen' => 'F1, O1',
            'tekst'    => "<p>Startpagina met vier knoppen: Logboek, Zoek nummers, Sla nummers op en Importeer nummers. De indeling (tekst links, navigatie rechts) volgt een bekende lay-out die gebruikers herkennen — een toepassing van cognitieve modellen in UI-ontwerp (O1).</p>"
        ],
        [
            'titel'    => '5. opslaan.php — nummers toevoegen',
            'domeinen' => 'C1, D1',
            'tekst'    => "<p>Formulier voor titel, artiest, genre (dropdown), jaar, album en een optionele link. Na versturen leest PHP de waarden via <code>\$_POST</code> en voert een INSERT uit:</p><pre>INSERT INTO nummer (titel, artiest, genre_id, jaar, album, link)\nVALUES ('Blinding Lights', 'The Weeknd', 1, 2019, 'After Hours', 'https://...')</pre><p>Bij opslaan verschijnt een confetti-animatie via de <code>canvas-confetti</code> bibliotheek. De kleur van de linkknop past zich automatisch aan via <code>str_contains()</code>. Er wordt ook gecheckt of de combinatie titel + artiest al bestaat — zo niet, dan wordt het nummer niet dubbel opgeslagen.</p>"
        ],
        [
            'titel'    => '6. zoek.php — combineerbaar zoeken en filteren',
            'domeinen' => 'C5, D1',
            'tekst'    => "<p>Alle filtervelden zijn optioneel en combineerbaar: titel/artiest, genre, jaar van en jaar tot. De WHERE-clausule wordt dynamisch opgebouwd:</p><pre>\$conditions = [];\nif (!empty(\$_GET['zoek_genre'])) \$conditions[] = \"n.genre_id = \$gid\";\nif (!empty(\$_GET['jaar_van']))   \$conditions[] = \"n.jaar >= \$jv\";\n\$where = !empty(\$conditions) ? \"WHERE \" . implode(\" AND \", \$conditions) : \"\";</pre><p>Zonder filter worden geen resultaten getoond. Via de knop \"Toon alles\" zie je alle nummers. Dit is een toepassing van <strong>C5</strong>: een informatiebehoefte vertalen naar een zoekopdracht op gestructureerde data.</p>"
        ],
        [
            'titel'    => '7. importeer.php — CSV-import',
            'domeinen' => 'C4, D1',
            'tekst'    => "<p>Via een bestandsupload kunnen meerdere nummers tegelijk worden toegevoegd vanuit een <code>.csv</code> bestand. CSV is een <strong>standaardrepresentatie</strong> voor tabeldata (C4). Zowel het eigen formaat als het Spotify-formaat wordt automatisch herkend via de kolomkoppen.</p><pre>Eigen formaat:   titel, artiest, genre, jaar, album, link\nSpotify-formaat: track_name, track_artist, playlist_genre, track_album_release_date, track_album_name</pre><p>Een helperfunctie zoekt per veld de juiste kolom op. Het jaar wordt slim uitgelezen: \"2019-06-14\" wordt automatisch \"2019\". Dubbelen worden overgeslagen. Je kunt per import instellen hoeveel nummers er tegelijk worden toegevoegd (max. 5000).</p>"
        ],
        [
            'titel'    => '8. Sorteerfunctie in zoek.php',
            'domeinen' => 'P2, O3',
            'tekst'    => "<p>De kolomkoppen in de zoekresultaten zijn klikbaar. Klikken sorteert op die kolom, nogmaals klikken draait de volgorde om. Een pijltje toont de actieve sortering.</p><pre>ORDER BY \$sort_sql \$sort_richting</pre><p><strong>Domein P2:</strong> De sortering zit in de kolomkop omdat gebruikers dat kennen van Excel en Spotify. De pijltjes geven directe visuele feedback.</p>"
        ],
        [
            'titel'    => '9. Bewerken en verwijderen in logboek.php',
            'domeinen' => 'D1, D2',
            'tekst'    => "<p>Alle inhoud van het logboek staat in de database. Systeem-blokken en persoonlijke wolkjes kunnen direct op de website worden bewerkt en verwijderd.</p><p>Bewerken klapt een formulier open via JavaScript (<code>style.display</code>). Opslaan stuurt een POST met <code>actie=bewerk</code>, verwijderen met <code>actie=verwijder</code>:</p><pre>UPDATE logboek SET tekst='...', titel='...' WHERE id=\$id\nDELETE FROM logboek WHERE id=\$id</pre><p>Na elke actie stuurt PHP de browser terug via <code>header('Location: logboek.php')</code> zodat het formulier niet opnieuw wordt verstuurd bij herladen.</p>"
        ],
        [
            'titel'    => '10. Zoekalgoritme in zoek.php — domein G',
            'domeinen' => 'G, C5',
            'tekst'    => "<p><strong>Domein G</strong> gaat over algoritmen: een reeks stappen die een probleem oplossen. In <code>zoek.php</code> zitten twee algoritmen:</p><p><strong>Algoritme 1 — dynamische WHERE-clausule opbouwen:</strong></p><pre>Stap 1: Begin met een lege lijst: \$conditions = []\nStap 2: Is er een zoekterm ingevuld?  → voeg voorwaarde toe aan lijst\n         Is er een genre gekozen?      → voeg voorwaarde toe aan lijst\n         Is er een beginjaar ingevuld? → voeg voorwaarde toe aan lijst\n         Is er een eindjaar ingevuld?  → voeg voorwaarde toe aan lijst\nStap 3: Is de lijst leeg? → geen WHERE-clausule (alle nummers tonen)\n         Is de lijst niet leeg? → combineer alle voorwaarden met AND</pre><p><strong>Algoritme 2 — relevantiesortering:</strong></p><pre>Exacte titelmatch   → prioriteit 0 (bovenaan)\nExacte artiestmatch → prioriteit 1\nGedeeltelijke match → prioriteit 2\nDaarna gesorteerd op de gekozen kolom</pre><p>In de tabel zichtbaar via het paarse <strong>exacte match</strong>-label.</p>"
        ],
    ];

    foreach ($blokken as $blok) {
        $titel    = addslashes($blok['titel']);
        $domeinen = addslashes($blok['domeinen']);
        $tekst    = addslashes($blok['tekst']);
        $database->selectRows("INSERT INTO logboek (soort, titel, domeinen, tekst) VALUES ('systeem', '$titel', '$domeinen', '$tekst')");
    }
}

// ── Data ophalen ──────────────────────────────────────────────────
$systeem_entries     = $database->selectRows("SELECT * FROM logboek WHERE soort='systeem'  ORDER BY id ASC");
$persoonlijk_entries = $database->selectRows("SELECT * FROM logboek WHERE soort='persoonlijk' ORDER BY datum DESC");
$volgend_nummer      = count($systeem_entries) + 1;
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logboek – Muziek Database</title>
    <link rel="stylesheet" href="muziek_style.css">
    <style>
        .pagina { min-height: auto; padding-bottom: 50px; }

        .logboek-wrapper {
            max-width: 860px;
            margin: 0 auto;
            padding: 0 30px;
        }

        .sectietitel {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
            margin: 35px 0 15px 0;
            letter-spacing: 1px;
        }

        /* ── Systeem-blokken ── */
        .gedaan-blok {
            background: rgba(255,255,255,0.42);
            border-radius: 12px;
            padding: 22px 26px;
            margin-bottom: 16px;
            position: relative;
        }

        .gedaan-blok h3 {
            font-size: 1.05rem;
            font-weight: bold;
            color: #4a3080;
            margin-bottom: 8px;
            padding-right: 120px;
        }

        .gedaan-blok p { font-size: 0.93rem; color: #333; line-height: 1.65; margin-bottom: 8px; }
        .gedaan-blok p:last-child { margin-bottom: 0; }

        .gedaan-blok code {
            background: rgba(201,160,224,0.25);
            border-radius: 4px;
            padding: 1px 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.88rem;
            color: #5a2080;
        }

        .gedaan-blok pre {
            background: rgba(80,40,120,0.1);
            border-left: 4px solid #c9a0e0;
            border-radius: 6px;
            padding: 12px 16px;
            font-family: 'Courier New', monospace;
            font-size: 0.82rem;
            color: #333;
            overflow-x: auto;
            margin: 10px 0;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .domein-tag {
            display: inline-block;
            background: #c9a0e0;
            color: white;
            font-size: 0.72rem;
            font-weight: bold;
            padding: 2px 9px;
            border-radius: 20px;
            margin: 0 2px;
        }

        .blok-acties {
            position: absolute;
            top: 16px;
            right: 16px;
            display: flex;
            gap: 6px;
        }

        .btn-bewerk, .btn-verwijder {
            border: none;
            border-radius: 6px;
            padding: 4px 12px;
            font-size: 0.8rem;
            font-weight: bold;
            cursor: pointer;
            font-family: Arial, sans-serif;
            transition: background-color 0.2s;
        }

        .btn-bewerk    { background: rgba(201,160,224,0.5); color: #4a3080; }
        .btn-bewerk:hover { background: rgba(201,160,224,0.8); }
        .btn-verwijder { background: rgba(220,100,100,0.2); color: #721c24; }
        .btn-verwijder:hover { background: rgba(220,100,100,0.4); }
        .btn-opslaan   { background: #c9a0e0; color: white; }
        .btn-opslaan:hover { background: #b080d0; }
        .btn-annuleer  { background: rgba(150,150,150,0.2); color: #555; }
        .btn-annuleer:hover { background: rgba(150,150,150,0.4); }

        .bewerk-form { display: none; margin-top: 12px; }

        .bewerk-form label {
            display: block;
            font-size: 0.85rem;
            font-weight: bold;
            color: #555;
            margin: 10px 0 4px;
        }

        .bewerk-form input[type="text"],
        .bewerk-form textarea,
        .nieuw-blok-form input[type="text"],
        .nieuw-blok-form textarea {
            width: 100%;
            padding: 8px 10px;
            font-size: 0.88rem;
            border: 2px solid #c9a0e0;
            border-radius: 6px;
            font-family: Arial, sans-serif;
            background: white;
            box-sizing: border-box;
        }

        .bewerk-form textarea,
        .nieuw-blok-form textarea {
            min-height: 120px;
            resize: vertical;
            font-family: 'Courier New', monospace;
        }

        .bewerk-form input:focus,
        .bewerk-form textarea:focus,
        .nieuw-blok-form input:focus,
        .nieuw-blok-form textarea:focus { outline: none; border-color: #9060c0; }

        .bewerk-knoppen { display: flex; gap: 8px; margin-top: 10px; }

        /* ── Nieuw blok toevoegen ── */
        .nieuw-blok-knop {
            background: rgba(201,160,224,0.3);
            border: 2px dashed #c9a0e0;
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            cursor: pointer;
            color: #4a3080;
            font-weight: bold;
            font-size: 0.95rem;
            margin-bottom: 16px;
            transition: background 0.2s;
            width: 100%;
            font-family: Arial, sans-serif;
        }

        .nieuw-blok-knop:hover { background: rgba(201,160,224,0.5); }

        .nieuw-blok-container {
            background: rgba(255,255,255,0.42);
            border-radius: 12px;
            padding: 22px 26px;
            margin-bottom: 16px;
            display: none;
        }

        .nieuw-blok-container h3 { font-size: 1rem; color: #4a3080; margin-bottom: 16px; }

        .nieuw-blok-form label {
            display: block;
            font-size: 0.85rem;
            font-weight: bold;
            color: #555;
            margin: 10px 0 4px;
        }

        .hint-tekst {
            background: rgba(201,160,224,0.15);
            border-left: 3px solid #c9a0e0;
            border-radius: 4px;
            padding: 10px 14px;
            font-size: 0.83rem;
            color: #555;
            margin: 6px 0 12px;
            line-height: 1.6;
        }

        /* ── Wolkjes ── */
        .voortgang-form {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 24px;
        }

        .voortgang-form textarea {
            width: 100%;
            padding: 12px 14px;
            font-size: 0.95rem;
            border: 2px solid #c9a0e0;
            border-radius: 10px;
            font-family: Arial, sans-serif;
            resize: vertical;
            min-height: 90px;
            background: rgba(255,255,255,0.7);
            box-sizing: border-box;
        }

        .voortgang-form textarea:focus { outline: none; border-color: #9060c0; }

        .voortgang-vorm-onderkant {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .voortgang-form button {
            background-color: #c9a0e0;
            color: white;
            border: none;
            padding: 10px 22px;
            font-size: 0.95rem;
            font-weight: bold;
            border-radius: 10px;
            cursor: pointer;
            font-family: Arial, sans-serif;
            white-space: nowrap;
            transition: background-color 0.2s;
        }

        .voortgang-form button:hover { background-color: #b080d0; }

        .enter-hint {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.65);
        }

        .wolken-gebied {
            max-height: 500px;
            overflow-y: auto;
            padding: 10px 6px;
            scrollbar-width: thin;
            scrollbar-color: #c9a0e0 transparent;
        }

        .wolken-gebied::-webkit-scrollbar { width: 6px; }
        .wolken-gebied::-webkit-scrollbar-thumb { background-color: #c9a0e0; border-radius: 10px; }

        .wolken-container { display: flex; flex-wrap: wrap; gap: 18px; align-items: flex-start; }

        .wolk {
            padding: 14px 18px;
            max-width: 280px;
            min-width: 160px;
            box-shadow: 2px 3px 10px rgba(150,100,200,0.18);
            line-height: 1.55;
            position: relative;
        }

        .wolk:nth-child(4n+1) { background: rgba(201,160,224,0.55); transform: rotate(-1.5deg); border-radius: 40px 40px 8px 40px; }
        .wolk:nth-child(4n+2) { background: rgba(230,195,245,0.6);  transform: rotate(1.2deg);  border-radius: 40px 8px 40px 40px; }
        .wolk:nth-child(4n+3) { background: rgba(245,215,255,0.65); transform: rotate(-0.8deg); border-radius: 8px 40px 40px 40px; }
        .wolk:nth-child(4n)   { background: rgba(215,175,240,0.58); transform: rotate(1.8deg);  border-radius: 40px 40px 40px 8px; }

        .wolk .wolk-datum { font-size: 0.72rem; color: #7a4aaa; font-weight: bold; margin-bottom: 5px; display: block; }
        .wolk .wolk-tekst { font-size: 0.9rem; color: #2a1a44; display: block; margin-bottom: 8px; }

        .wolk-acties { display: flex; gap: 5px; margin-top: 6px; }
        .wolk-acties .btn-bewerk,
        .wolk-acties .btn-verwijder { font-size: 0.72rem; padding: 3px 9px; }

        .wolk-bewerk-form { display: none; margin-top: 8px; }
        .wolk-bewerk-form textarea {
            width: 100%;
            padding: 6px 8px;
            font-size: 0.85rem;
            border: 2px solid #9060c0;
            border-radius: 6px;
            font-family: Arial, sans-serif;
            min-height: 60px;
            resize: vertical;
            box-sizing: border-box;
            background: white;
        }

        .geen-entries { color: rgba(255,255,255,0.8); font-style: italic; }
        .terug { display: inline-block; margin: 30px 0 0 0; }
    </style>
</head>
<body>

<div class="pagina">
    <header>
        <h1>MUZIEK DATABASE</h1>
        <p>Informatica VWO 6</p>
    </header>

    <div class="logboek-wrapper">

        <div class="sectietitel">Wat er gedaan is</div>

        <?php foreach ($systeem_entries as $blok): ?>
            <div class="gedaan-blok" id="blok-<?= $blok['id'] ?>">
                <div class="blok-acties">
                    <button class="btn-bewerk"    onclick="bewerkTonen(<?= $blok['id'] ?>)">Bewerk</button>
                    <button class="btn-verwijder" onclick="bevestigVerwijder(<?= $blok['id'] ?>, 'blok')">Verwijder</button>
                </div>

                <div id="inhoud-<?= $blok['id'] ?>">
                    <h3>
                        <?= htmlspecialchars($blok['titel']) ?>
                        <?php if (!empty($blok['domeinen'])): ?>
                            <?php foreach (explode(',', $blok['domeinen']) as $d): ?>
                                <span class="domein-tag"><?= htmlspecialchars(trim($d)) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </h3>
                    <?php
                        $t = stripslashes($blok['tekst']);
                        // Bevat het HTML-tags? Dan direct renderen, anders enters omzetten
                        echo (strpos($t, '<') !== false)
                            ? $t
                            : '<p style="white-space:pre-wrap;">' . htmlspecialchars($t) . '</p>';
                    ?>
                </div>

                <div class="bewerk-form" id="bewerk-form-<?= $blok['id'] ?>">
                    <form method="POST" action="logboek.php">
                        <input type="hidden" name="actie" value="bewerk">
                        <input type="hidden" name="id"    value="<?= $blok['id'] ?>">
                        <label>Titel:</label>
                        <input type="text" name="titel" value="<?= htmlspecialchars($blok['titel']) ?>">
                        <label>Domeinen (bijv. C1, D2):</label>
                        <input type="text" name="domeinen" value="<?= htmlspecialchars($blok['domeinen'] ?? '') ?>">
                        <label>Inhoud:</label>
                        <textarea name="tekst"><?= htmlspecialchars($blok['tekst']) ?></textarea>
                        <div class="bewerk-knoppen">
                            <button type="submit" class="btn-bewerk btn-opslaan">Opslaan</button>
                            <button type="button" class="btn-bewerk btn-annuleer" onclick="bewerkVerbergen(<?= $blok['id'] ?>)">Annuleren</button>
                        </div>
                    </form>
                </div>

                <form id="verwijder-form-<?= $blok['id'] ?>" method="POST" action="logboek.php" style="display:none;">
                    <input type="hidden" name="actie" value="verwijder">
                    <input type="hidden" name="id"    value="<?= $blok['id'] ?>">
                </form>
            </div>
        <?php endforeach; ?>

        <!-- Nieuw systeem-blok toevoegen -->
        <button class="nieuw-blok-knop" onclick="nieuwBlokTonen()">+ Nieuw blok toevoegen</button>

        <div class="nieuw-blok-container" id="nieuw-blok-container">
            <h3>Nieuw blok toevoegen</h3>

            <div class="hint-tekst">
                <strong>Titel:</strong> Gebruik een volgnummer, bijv. "<?= $volgend_nummer ?>. Naam van het bestand of functie"<br>
                <strong>Domeinen:</strong> Vul de domeinletters in die van toepassing zijn, bijv. "D1, C5"<br>
                <strong>Inhoud:</strong> Beschrijf wat er is gebouwd, hoe het werkt en welk domein het dekt.
            </div>

            <form method="POST" action="logboek.php" class="nieuw-blok-form">
                <input type="hidden" name="actie" value="toevoegen_systeem">
                <label>Titel:</label>
                <input type="text" name="titel" placeholder="bijv. <?= $volgend_nummer ?>. zoek.php — sorteerfunctie">
                <label>Domeinen:</label>
                <input type="text" name="domeinen" placeholder="bijv. P2, O3">
                <label>Inhoud:</label>
                <textarea name="tekst" placeholder="Beschrijf hier wat er is gemaakt en hoe het werkt..."></textarea>
                <div class="bewerk-knoppen" style="margin-top:12px;">
                    <button type="submit" class="btn-bewerk btn-opslaan">Toevoegen</button>
                    <button type="button" class="btn-bewerk btn-annuleer" onclick="nieuwBlokVerbergen()">Annuleren</button>
                </div>
            </form>
        </div>

        <!-- Mijn voortgang -->
        <div class="sectietitel">Mijn voortgang</div>

        <form method="POST" action="logboek.php" class="voortgang-form" id="voortgang-form">
            <input type="hidden" name="actie" value="toevoegen">
            <textarea name="tekst" id="voortgang-tekst"
                      placeholder="Schrijf hier wat je vandaag hebt gedaan... (Enter = nieuwe regel, Shift+Enter = versturen)"></textarea>
            <div class="voortgang-vorm-onderkant">
                <button type="submit">Toevoegen</button>
                <span class="enter-hint">Shift+Enter om te versturen</span>
            </div>
        </form>

        <div class="wolken-gebied">
            <?php if (empty($persoonlijk_entries)): ?>
                <p class="geen-entries">Nog geen voortgang ingevoerd.</p>
            <?php else: ?>
                <div class="wolken-container">
                    <?php foreach ($persoonlijk_entries as $entry): ?>
                        <div class="wolk">
                            <span class="wolk-datum"><?= date('d-m-Y H:i', strtotime($entry['datum'])) ?></span>
                            <span class="wolk-tekst" id="wolk-tekst-<?= $entry['id'] ?>">
                                <?= nl2br(htmlspecialchars(stripslashes($entry['tekst']))) ?>
                            </span>
                            <div class="wolk-bewerk-form" id="wolk-bewerk-<?= $entry['id'] ?>">
                                <form method="POST" action="logboek.php">
                                    <input type="hidden" name="actie"    value="bewerk">
                                    <input type="hidden" name="id"       value="<?= $entry['id'] ?>">
                                    <input type="hidden" name="titel"    value="">
                                    <input type="hidden" name="domeinen" value="">
                                    <textarea name="tekst"><?= htmlspecialchars(stripslashes($entry['tekst'])) ?></textarea>
                                    <div class="bewerk-knoppen" style="margin-top:6px;">
                                        <button type="submit" class="btn-bewerk btn-opslaan" style="font-size:0.75rem;padding:3px 10px;">Opslaan</button>
                                        <button type="button" class="btn-bewerk btn-annuleer" style="font-size:0.75rem;padding:3px 10px;" onclick="wolkVerbergen(<?= $entry['id'] ?>)">Annuleren</button>
                                    </div>
                                </form>
                            </div>
                            <div class="wolk-acties">
                                <button class="btn-bewerk"    onclick="wolkTonen(<?= $entry['id'] ?>)">Bewerk</button>
                                <button class="btn-verwijder" onclick="bevestigVerwijder(<?= $entry['id'] ?>, 'wolk')">Verwijder</button>
                            </div>
                            <form id="verwijder-wolk-<?= $entry['id'] ?>" method="POST" action="logboek.php" style="display:none;">
                                <input type="hidden" name="actie" value="verwijder">
                                <input type="hidden" name="id"    value="<?= $entry['id'] ?>">
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <a href="index.php" class="knop terug">Terug</a>

    </div>
</div>

<script>
    // ── Alle textareas: Enter = versturen, Shift+Enter = nieuwe regel ─
    document.querySelectorAll('textarea').forEach(function(ta) {
        ta.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                ta.closest('form').submit();
            }
            // Shift+Enter = nieuwe regel
        });
    });

    function bewerkTonen(id) {
        document.getElementById('inhoud-' + id).style.display      = 'none';
        document.getElementById('bewerk-form-' + id).style.display = 'block';
    }
    function bewerkVerbergen(id) {
        document.getElementById('inhoud-' + id).style.display      = 'block';
        document.getElementById('bewerk-form-' + id).style.display = 'none';
    }
    function wolkTonen(id) {
        document.getElementById('wolk-tekst-' + id).style.display  = 'none';
        document.getElementById('wolk-bewerk-' + id).style.display = 'block';
    }
    function wolkVerbergen(id) {
        document.getElementById('wolk-tekst-' + id).style.display  = 'inline';
        document.getElementById('wolk-bewerk-' + id).style.display = 'none';
    }
    function nieuwBlokTonen() {
        document.getElementById('nieuw-blok-container').style.display = 'block';
        document.querySelector('.nieuw-blok-knop').style.display       = 'none';
    }
    function nieuwBlokVerbergen() {
        document.getElementById('nieuw-blok-container').style.display = 'none';
        document.querySelector('.nieuw-blok-knop').style.display       = 'block';
    }
    function bevestigVerwijder(id, type) {
        if (confirm('Weet je zeker dat je dit wilt verwijderen?')) {
            var formId = (type === 'wolk') ? 'verwijder-wolk-' + id : 'verwijder-form-' + id;
            document.getElementById(formId).submit();
        }
    }
</script>

</body>
</html>
