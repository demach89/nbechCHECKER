<?php

namespace Core\NBKI;
use Core\Provider\CSVProvider;


/**
 * Класс для работы с выгрузкой НБКИ
 * Выгрузка — файл без расширения, имеющий имя вида DT01SS000001_20240328_082757
 *
 * Class NBKIReport
 * @package Core\NBKI
 */
class NBKIReport
{
    protected CSVProvider $CSVProvider;
    protected array $csvData = [];
    protected NBKIParser $parser;
    protected string $fileName;

    public function __construct()
    {
        $this->CSVProvider = new CSVProvider();
        $this->parser = new NBKIParser();
    }

    /**
     * Импорт НБКИ-отчёта
     * @param string $fileName
     * @param string $separator
     * @param int $skipHeadersCount
     * @return NBKIReport
     */
    public function import(string $fileName, string $separator = ';', int $skipHeadersCount = 0) : self
    {
        $this->fileName = $fileName;

        try {
            $this->csvData = $this->CSVProvider->importAsCSV_ussoc($this->fileName, $separator, $skipHeadersCount)->getData();
        } catch (\Throwable $e) {
            echo __CLASS__ . "|" . __FUNCTION__ . "|Ошибка импорта отчёта: {$e->getMessage()}\n";
            exit();
        }

        return $this;
    }

    /**
     * Парсить данные отчёта на предмет ошибок
     * @return $this
     */
    public function parse() : self
    {
        $this->parser->parse($this->csvData);

        return $this;
    }

    /**
     * Экспорт ошибок НБКИ-отчёта
     * @param string $fileName
     * @param string $separator
     * @return $this
     */
    /*
    public function export(string $fileName, string $separator = ';') : self
    {
        try {
            $fileErrorsName = $fileName . "_errors.txt";
            $this->CSVProvider->exportAsCSV_ANSI($this->getErrorsData(), $fileErrorsName, $separator);

            $fileAllocatedErrorsName = $fileName . "_errors_allocated.txt";
            $this->CSVProvider->exportAsCSV_ANSI($this->getAllocatedErrorsData(), $fileAllocatedErrorsName, $separator);
        } catch (\Throwable $e) {
            echo __CLASS__ . "|" . __FUNCTION__ . "|Ошибка выгрузки отчёта: {$e->getMessage()}\n";
            exit();
        }

        return $this;
    }
    */

    /**
     * Получить данные НБКИ-отчёта
     */
    public function getParsed() : array
    {
        return $this->parser->getParsed();
    }

    /**
     * Получить ошибки НБКИ-отчёта
     */
    public function getErrors() : array
    {
        return [
            'parse' => $this->parser->getErrors(),
        ];
    }


}