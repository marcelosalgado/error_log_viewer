<?php

class LogDisplayer
{
    public $readLog;
    public $nomeServidor;
    public $errorLog;
    public $sqlMode = false;

    public static $configs;

    public static function setConfigs($newConfigs)
    {
        self::$configs = $newConfigs;
    }

    public static function getConfigs()
    {
        return self::$configs;
    }

    public $options = [
        'tipo'        => 'php',
        'flush'       => false,
        'flush_all'   => false,
        'clear_cache' => false,
        'linhas'      => 100,
        'filtro'      => '',
        'aPartirDe'   => '',
        'ignorar'     => '',
    ];

    public function __construct($get)
    {
        foreach ($this->options as $key => $value) {
            if (isset($get[$key])) {
                $this->options[$key] = $get[$key];
            }
        }

        if (!$this->options['tipo']) {
            $this->options['tipo'] = 'php';
        }
        $this->nomeServidor = $this->options['tipo'];

        $this->loadConfigs();
        $this->errorLog   = self::$configs[$this->options['tipo']]['arquivo'];
        $this->sqlMode    = self::$configs[$this->options['tipo']]['sqlMode'];
        $this->tag        = self::$configs[$this->options['tipo']]['tag'];
        $this->currentLog = self::$configs[$this->options['tipo']];
    }

    private function loadConfigs()
    {
        if (is_file('configs.php')) {
            require_once('configs.php');
        } else {
            require_once('configs.php.default');
        }
    }

    private function readLastLines($file, $lines, $filtro = '', $aPartirDe = '', $ignorar = [])
    {
        if (is_string($file)) {
            if (!file_exists($file)) {
                return false;
            }
            $fh = fopen($file, "r");
            if ($fh === false) {
                return false;
            }
        } else {
            $fh = $file;
        }

        fseek($fh, 0, SEEK_END);

        $position = $this->seekLineBack($fh, $lines);
        $lines = [];

        $aPartirAtivo = !empty($aPartirDe);
        $filtroAtivo = !empty($filtro);
        $ignorarAtivo = count($ignorar) != 0;
        $contador = 0;
        $ignorados = 0;

        while ($line = fgets($fh)) {
            if ($aPartirAtivo) {
                if (strpos($line, $aPartirDe) === false) {
                    continue;
                } else {
                    $aPartirAtivo = false;
                }
            }
            if ($filtroAtivo && strpos($line, $filtro) === false) {
                continue;
            }
            if ($ignorarAtivo) {
                $pular = false;
                foreach ($ignorar as $id => $atual) {
                    if (!empty($atual) && !empty($line) && strpos($line, $atual) !== false) {
                        $pular = true;
                        break;
                    }
                }
                if ($pular) {
                    $ignorados++;
                    continue;
                }
            }
            if (empty($this->sqlMode)) {
                $line = str_replace('\\n', '<br />', $line);
                $lines[] = $line;
            } else {

                if ($temp = preg_match("/" . $this->sqlMode . "/", $line)) {
                    $contador++;
                    $line = substr($line, 0, 37)."<br />".substr($line, 37);
                    $lines[] = $line;
                } else {
                    //$lines[$contador] .= $line."<br/>";
                    $lines[$contador] = isset($lines[$contador]) ? $lines[$contador] : '';
                    $lines[$contador] .= $line."<br />";
                }
            }
        }

        if (is_string($file)) {
            fclose($fh);
        }

        return $lines;
    }

    /**
     * http://stackoverflow.com/questions/2961618/how-to-read-only-5-last-line-of-the-txt-file
     */
    private function seekLineBack($fh, $n)
    {
        $readSize = 160 * ($n + 1);
        $pos = ftell($fh);

        if (ftell($fh) === 0) {
            return false;
        }

        if ($pos === false) {
            fseek($fh, 0, SEEK_SET);

            return false;
        }

        while ($n >= 0) {
            if ($pos === 0) {
                break;
            }

            $currentReadsize = $readSize;
            $pos = $pos - $readSize;
            if ($pos < 0) {
                $currentReadsize = $readSize - abs($pos);
                $pos = 0;
            }

            if (fseek($fh, $pos, SEEK_SET) === -1) {
                fseek($fh, 0, SEEK_SET);
                break;
            }

            $data = fread($fh, $currentReadsize);
            $count = substr_count($data, "\n");
            $n = $n - $count;

            if ($n < 0) {
                break;
            }
        }

        fseek($fh, $pos, SEEK_SET);

        while ($n < 0) {
            fgets($fh);
            $n++;
        }

        $pos = ftell($fh);
        if ($pos === false) {
            fseek($fh, 0, SEEK_SET);
        }

        return $pos;
    }

    private function normalizeSlashes($path)
    {
        return str_replace('\\', '/', $path);
    }

    public function preProcesso()
    {
        if ($this->options['flush']) {
            $this->flush($this->errorLog);
        }
        if ($this->options['flush_all']) {
            foreach (self::$configs as $logAtual) {
                $this->flush($logAtual['arquivo']);
            }
        }
        if ($this->options['clear_cache']) {
            $this->clearCache();
        }
    }

    public function clearCache()
    {
        // TODO custom cache clearing functions here
    }

    public function processaLog()
    {
        $erro = null;
        if (!file_exists($this->errorLog)) {
            $erro = 'Arquivo de log nao existe!';
        } elseif (!is_readable($this->errorLog)) {
            $erro ='Arquivo de log nao esta acessivel!';
        }

        if ($erro) {
            $user = posix_getpwuid(posix_geteuid());
            $userGroup = $user['name'] ?? 'www-data:www-data';

            $this->readLog = [
                $erro . "</br>
                Sugestao:</br>
                sudo touch {$this->errorLog}</br>
                sudo chown -R {$userGroup} {$this->errorLog}</br>
                sudo chmod -R 775 {$this->errorLog}</br>
                usuario atual:  ".$user['name'].""."</br>
                ",
            ];

            return $this->readLog;
        }

        $this->options['linhas'] = $this->options['linhas'] ? $this->options['linhas'] : 100;
        $this->options['linhas'] = $this->options['linhas'] < 5 ? 5 : $this->options['linhas'];
        $this->options['linhas'] = $this->options['linhas'] > 99999 ? 99999 : $this->options['linhas'];

        $this->options['ignorar'] = array_filter(explode('|', $this->options['ignorar']));

        if (!empty($this->sqlMode) && empty($this->options['ignorar'])) {
            $this->options['ignorar'] = ['TYPNAME', 'DEALLOCATE', 'RELNAME'];
        }

        $this->readLog = $this->readLastLines(
            $this->errorLog,
            $this->options['linhas'],
            $this->options['filtro'],
            $this->options['aPartirDe'],
            $this->options['ignorar']
        );

        return $this->readLog;
    }

    public function display()
    {
        $GLOBALS['nomeServidor'] = $this->nomeServidor;
        $GLOBALS['errorLog']     = $this->errorLog;
        $GLOBALS['numLinhas']    = $this->options['linhas'];
        $GLOBALS['filtro']       = $this->options['filtro'];
        $GLOBALS['aPartirDe']    = $this->options['aPartirDe'];
        $GLOBALS['ignorar']      = $this->options['ignorar'];
        $GLOBALS['readLog']      = $this->readLog;
        $GLOBALS['currentLog']   = $this->currentLog;
        $GLOBALS['tag']          = $this->tag;

        include 'view_logs.php';
    }

    public function flush($file)
    {
        $flushed = @fopen($file, "r+");
        @ftruncate($flushed, 0);
        header("Location:index.php?tipo=".$this->nomeServidor);
    }
}
