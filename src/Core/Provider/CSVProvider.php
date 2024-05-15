<?php

namespace Core\Provider;

/**
 * Класс импорта\экспорта текстовых данных
 * Class CSVProvider
 * @package Core\Provider
 */
Class CSVProvider
{
    protected array  $data;
    protected string $importPath;
    protected string $exportPath;

    protected string $separator;
    protected int    $skipHeadersCounter;

    /**
     * Импорт данных в не-ассоц.массив
     * Конвертация в UTF-8 позволяет избежать смешивания кодировок, например:
     *    стандартный CSV в ANSI кодировке, при последующем добавлении в массив с ANSI-строками вручную введённые строки (UTF-8),
     *    при выгрузке файл становится нечитаемым (смешанные кодировки).
     */
    public function importAsCSV_ussoc(string $fileName, string $separator=";", int $skipHeadersCounter=0) : self
    {
        $this->importPath = IMPORT_DIR . "/$fileName";

        $this->separator = $separator;

        $this->skipHeadersCounter = $skipHeadersCounter;

        try {
            if (file_exists($this->importPath)) {
                $content = file_get_contents($this->importPath);
            } else {
                throw new \Error("Файл не существует");
            }

            // Конвертация в UTF-8 по умолчанию
            // При стандартном чтении ANSI-строки при работе с консолью выводятся пустыми
            $content = (mb_check_encoding($content, 'UTF-8'))?
                $content : iconv("CP1251//TRANSLIT", "UTF-8", $content);

            $lines = explode("\n", $content);
            $lines = array_filter($lines);

            $this->filterEmpty($lines);
            $this->skipHeaders($lines);

            // Создание не-ассоц. массива из полей строк
            $ussoc = array_map(
                fn($line) => explode($this->separator, $line),
                $lines
            );

        } catch (\Throwable $e) {
            echo $e->getMessage() . PHP_EOL;

            exit();
        }

        $this->data = $ussoc;

        return $this;
    }

    /**
     * Импорт данных в ассоц.массив
     * Конвертация в UTF-8 позволяет избежать смешивания кодировок, например:
     *    стандартный CSV в ANSI кодировке, при последующем добавлении в массив с ANSI-строками вручную введённые строки (UTF-8),
     *    при выгрузке файл становится нечитаемым (смешанные кодировки).
     */
    public function importAsCSV_assoc(string $fileName, string $separator=";", int $skipHeadersCounter=0) : self
    {
        $this->importAsCSV_ussoc($fileName, $separator, $skipHeadersCounter);

        $ussoc = $this->data;

        $header = array_shift($ussoc);
        $assoc = [];

        foreach ($ussoc as $u) {
            $assoc[] = array_combine($header, $u);
        }

        $this->data = $assoc;

        return $this;
    }

    /**
     * Сохранить массив данных в текстовый файл в виде строк с разделителем
     * Кодировка UTF-8
     */
    public function exportAsCSV_UTF8(array $data, string $fileName, string $separator=";") : self
    {
        $exportPath = EXPORT_DIR . "/$fileName";

        try {
            $fp = fopen($exportPath, 'w');

            foreach ($data as $datum) {
                fwrite($fp, implode($separator, $datum) . "\n");
            }

            fclose($fp);
        } catch (\Throwable $e) {
            echo __CLASS__ . '|' . __FUNCTION__ . '|' . $e->getMessage() . PHP_EOL;

            exit();
        }

        return $this;
    }

    /**
     * Сохранить массив данных в текстовый файл в виде строк с разделителем
     * Кодировка ANSI
     */
    public function exportAsCSV_ANSI(array $data, string $fileName, string $separator=";") : self
    {
        $this->exportAsCSV_UTF8($data, $fileName, $separator);
        $this->fileUTF8toANSI($fileName);

        return $this;
    }

    /**
     * Конвертация TXT-файла из UTF-8 в ANSI
     */
    public function fileUTF8toANSI(string $fileName) : self
    {
        $path = EXPORT_DIR . "/$fileName";

        try {
            if (file_exists($path)) {
                $content = file_get_contents($path);

                if (mb_check_encoding($content, 'UTF-8')) {
                    $content = iconv("UTF-8", "CP1251//TRANSLIT", $content);
                    file_put_contents($path, $content);
                }
            } else {
                throw new ('fileUTF8toANSI: Исходный файл не существует.');
            }
        } catch (\Throwable $e) {
            echo __CLASS__ . '|' . __FUNCTION__ . '|' . $e->getMessage() . PHP_EOL;

            exit();
        }

        return $this;
    }

    /**
     * Убрать пустые строки
     * @param array $lines
     * @return void
     */
    protected function filterEmpty(array &$lines) : void
    {
        $lines = array_filter($lines); // убирает пустые строки (актуально для концовки)

        $lines = array_filter(       // убирает пустые строки с разделителем (актуально для концовки)
            $lines,
            fn($str) => !preg_match("/^[$this->separator]+$/u", $str)
        );
    }

    /**
     * Пропустить первые строки
     * @param array $csv
     */
    protected function skipHeaders(array &$csv) : void
    {
        for ($i = 1; $i <= $this->skipHeadersCounter; $i++) {
            array_shift($csv);
        }
    }

    /**  */
    public function getData() : array
    {
        return $this->data;
    }

}