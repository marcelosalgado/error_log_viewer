<?php

$size = 0;
if (is_file($GLOBALS['errorLog'])) {
    $size = round(filesize($GLOBALS['errorLog']) / (1024*1024), 2);
}

$ignorar = implode("|", empty($GLOBALS['ignorar']) ? [] : $GLOBALS['ignorar']);
?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="ISO-8859-1">
        <title><?= $GLOBALS['nomeServidor'] ?></title>
        <link href="style.css" rel="stylesheet" type="text/css">
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
    </head>

    <body>
        <header>
            <h3>Log do <?= $GLOBALS['nomeServidor'] ?> <?= $GLOBALS['errorLog'] ?></h3>
            <div style="float:right;">
            <?php
            $configs = LogDisplayer::getConfigs();
            foreach ($configs as $key => $log) {
                ?>
                <a class='links' href="index.php?tipo=<?= $key ?>"><?= $log['nome'] ?></a>
                <?php

            }
            ?>
            </div>
        </header>
        <nav>
            <p>
                File size: <?= $size ?> Mbytes
                <a href="index.php?tipo=<?= $GLOBALS['nomeServidor'] ?>&flush=1" class='links'>FLUSH</a>
                <a href="index.php?tipo=<?= $GLOBALS['nomeServidor'] ?>&flush_all=1" class='links'>FLUSH ALL</a>
                <a href="index.php?tipo=<?= $GLOBALS['nomeServidor'] ?>&clear_cache=1" class='links'>CLEAR CACHE</a>
                <br />
                Number of lines: <?= count($GLOBALS['readLog']); ?> <br />
            </p>
            <p>
                <form name="input" action="index.php" method="get">
                    Lines to fetch: <input type="text" name="linhas" value="<?= $GLOBALS['numLinhas'] ?>" maxlength="5" style="width:40px;"/>&nbsp;
                    Filter: <input type="text" name="filtro" value="<?= $GLOBALS['filtro'] ?>" maxlength="300" style="width:400px;"/>&nbsp;
                    Starting at: <input type="text" name="aPartirDe" value="<?= $GLOBALS['aPartirDe'] ?>" maxlength="300" style="width:80px;"/>&nbsp;
                    Ignore lines with: <input type="text" name="ignorar" value="<?= $ignorar; ?>" maxlength="300" style="width:80px;"/>&nbsp;
                    <input type="hidden" name="tipo" value="<?= $GLOBALS['nomeServidor'] ?>" />
                    <input type="submit" value="Vai" />
                </form>
            </p>
        </nav>
        <section>
            <p>
                <?php
                $num = 0;
                foreach ($GLOBALS['readLog'] as $temp => $atual) {
                    $num++;
                    $atual = $GLOBALS['currentLog']['specialTag'][0]
                        .$atual
                        .$GLOBALS['currentLog']['specialTag'][1];
                    ?>
                    <<?= $GLOBALS['tag']; ?> class="<?= $num % 2 == 0 ? 'par' : 'impar' ?> log <?php
                        echo $GLOBALS['currentLog']['specialClass'];
                    ?>"><?= $atual ?></<?= $GLOBALS['tag']; ?>>
                    <?php

                }
                ?>
            </p>
        </section>
        <div class="floating_flush">
            <a href="index.php?tipo=<?= $GLOBALS['nomeServidor'] ?>&flush_all=1" class='links'>FLUSH</a>
            <a href="#" class='links'>TOP</a>
        </div>
    </body>
</html>
