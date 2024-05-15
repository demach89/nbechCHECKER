<?php

namespace Core\NBKI;
use Core\NBKI\Checker\BlockSizeChecker;

require_once __DIR__ . "/../../libs/func.php";

class NBKIParser
{
    protected array $nbkiData = [];
    protected array $errors = [];

    protected int $currentGroupCounter = 1;

    /**
     * Сопоставить поля реестра с параметрами НБКИ (RUTDF)
     * @param array $rows
     * @return NBKIParser
     */
    public function parse(array $rows) : self
    {
        $this->excludeServiceFields($rows);

        $rowCount = count($rows);
        $contractData = ['blocks' => []];

        foreach ($rows as $rowNum => $row) {
            $blockName = $row[0];

            // Добавление накопленных данных по договору
            if ($blockName === '0_GROUPHEADER' && $rowNum !== 0) {
                $this->nbkiData['contracts'][] = $contractData;
                $contractData = ['blocks' => []];
            }
            
            try {
                $block = match ($blockName) {
                    '0_GROUPHEADER' => $this->get_0_GROUPHEADER($row),
                    'C1_NAME' => $this->get_C1_NAME($row),
                    'C2_PREVNAME' => $this->get_C2_PREVNAME($row),
                    'C3_BIRTH' => $this->get_C3_BIRTH($row),
                    'C4_ID' => $this->get_C4_ID($row),
                    'C5_PREVID' => $this->get_C5_PREVID($row),
                    'C6_REGNUM' => $this->get_C6_REGNUM($row),
                    'C7_SNILS' => $this->get_C7_SNILS($row),
                    'C17_UID' => $this->get_C17_UID($row),
                    'C18_TRADE' => $this->get_C18_TRADE($row),
                    'C19_ACCOUNTAMT' => $this->get_C19_ACCOUNTAMT($row),
                    'C21_PAYMTCONDITION' => $this->get_C21_PAYMTCONDITION($row),
                    'C22_OVERALLVAL' => $this->get_C22_OVERALLVAL($row),
                    'C24_FUNDDATE' => $this->get_C24_FUNDDATE($row),
                    'C25_ARREAR' => $this->get_C25_ARREAR($row),
                    'C26_DUEARREAR' => $this->get_C26_DUEARREAR($row),
                    'C27_PASTDUEARREAR' => $this->get_C27_PASTDUEARREAR($row),
                    'C28_PAYMT' => $this->get_C28_PAYMT($row),
                    'C29_MONTHAVERPAYMT' => $this->get_C29_MONTHAVERPAYMT($row),
                    'C38_OBLIGTERMINATION' => $this->get_C38_OBLIGTERMINATION($row),
                    'C54_OBLIGACCOUNT' => $this->get_C54_OBLIGACCOUNT($row),
                    'C56_OBLIGPARTTAKE' => $this->get_C56_OBLIGPARTTAKE($row),
                    default => null,
                };
            } catch (\Throwable $e) {
                $this->errors[] = __METHOD__ . "|" . $e->getMessage() . " (row=". ($rowNum+2) .")"; //2=HEADER+нумерация строк в цикле с 0
            }
            
            if (isset($block)) {
                $contractData['blocks'] += $block;
            }

            // Добавление последнего
            if ($rowNum === $rowCount-1) {
                $this->nbkiData['contracts'][] = $contractData;
            }

            $contractData['groupCounter'] = $this->currentGroupCounter;
        }

        return $this;
    }

    protected function excludeServiceFields(array &$rows) : void
    {
        $this->parseServiceFields($rows);
    }
            protected function parseServiceFields(array &$rows) : void
            {
                $this->parseHeader($rows);
                $this->pasrseTrailer($rows);
            }

            protected function parseHeader(array &$rows) : void
            {
                try {
                    $header = array_shift($rows);
                    $this->nbkiData += $this->get_HEADER($header);
                } catch (\Throwable $e) {
                    $this->errors[] = __METHOD__ . "|" . $e->getMessage();
                }
            }
            protected function pasrseTrailer(array &$rows) : void
            {
                try {
                    $trailer = array_pop($rows);
                    $this->nbkiData += $this->get_TRAILER($trailer);
                } catch (\Throwable $e) {
                    $this->errors[] = __METHOD__ . "|" . $e->getMessage();
                }
            }

    /**
     * Заголовок
     * @param array $data
     * @return array
     */
    protected function get_HEADER(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'             => $data[0],
                'CUSTOMER_INN'      => isset($data[1]) ? (string)$data[1] : null,
                'CUSTOMER_OGRN'     => isset($data[2]) ? (string)$data[2] : null,
                'filename'          => isset($data[3]) ? (string)$data[3] : null,
                'regDate'           => isset($data[4]) ? (string)$data[4] : null,
                //'not_used_field'  => isset($data[5]) ? (string)$data[5] : null,
                'CUSTOMER_CODE'     => isset($data[6]) ? (string)$data[6] : null,
                'CUSTOMER_PWD'      => isset($data[7]) ? (string)$data[7] : null,
                'RUTDF_VER'         => isset($data[8]) ? (string)$data[8] : null,
                //'not_used_field'  => isset($data[9]) ? (string)$data[9] : null,
                'formDate'          => isset($data[10]) ? (string)$data[10] : null,
                //'not_used_field'  => isset($data[11]) ? (string)$data[11] : null,
                //'not_used_field'  => isset($data[12]) ? (string)$data[12] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Заголовок группы блоков
     * @param array $data
     * @return array
     */
    protected function get_0_GROUPHEADER(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'         => $data[0],
                'groupCounter'  => isset($data[1]) ? (int)$data[1] : null,
                'eventNumber'   => isset($data[2]) ? (string)$data[2] : null,
                'operationCode' => isset($data[3]) ? (string)$data[3] : null,
                'comment'       => isset($data[4]) ? (string)$data[4] : null,
                'date'          => isset($data[5]) ? (string)$data[5] : null,
            ];

            $this->currentGroupCounter = $block[$blockName]['groupCounter'] ?? uniqid('undef_', true);
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C1_NAME'
     * ФИО
     * @param array $data
     * @return array
     */
    protected function get_C1_NAME(array $data) : array
    {
        $blockName = $data[0];

        try {
            BlockSizeChecker::check($blockName, count($data));

            $block[$blockName] = [
                'block'     => $data[0],
                'name1'     => isset($data[1]) ? (string)$data[1] : null,
                'first'     => isset($data[2]) ? (string)$data[2] : null,
                'paternal'  => isset($data[3]) ? (string)$data[3] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C2_PREVNAME'
     * ФИО предыдущее
     * @param array $data
     * @return array
     */
    protected function get_C2_PREVNAME(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'         => $data[0],
                'isPrevName'    => isset($data[1]) ? (string)$data[1] : null,
                'name1'         => isset($data[2]) ? (string)$data[2] : null,
                'first'         => isset($data[3]) ? (string)$data[3] : null,
                'paternal'      => isset($data[4]) ? (string)$data[4] : null,
                'issueDate'     => isset($data[5]) ? (string)$data[5] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C3_BIRTH'
     * Дата и место рождения
     * @param array $data
     * @return array
     */
    protected function get_C3_BIRTH(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'         => $data[0],
                'birthDt'       => isset($data[1]) ? (string)$data[1] : null,
                'OKSM'          => isset($data[2]) ? (string)$data[2] : null,
                'placeOfBirth'  => isset($data[3]) ? (string)$data[3] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C4_ID'
     * Документ, удостоверяющий личность
     * @param array $data
     * @return array
     */
    protected function get_C4_ID(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'             => $data[0],
                'OKSM'              => isset($data[1]) ? (string)$data[1] : null,
                'otherCountry'      => isset($data[2]) ? (string)$data[2] : null,
                'idType'            => isset($data[3]) ? (string)$data[3] : null,
                'otherId'           => isset($data[4]) ? (string)$data[4] : null,
                'seriesNumber'      => isset($data[5]) ? (string)$data[5] : null,
                'idNum'             => isset($data[6]) ? (string)$data[6] : null,
                'issueDate'         => isset($data[7]) ? (string)$data[7] : null,
                'issueAuthority'    => isset($data[8]) ? (string)$data[8] : null,
                'divCode'           => isset($data[9]) ? (string)$data[9] : null,
                'validTo'           => isset($data[10]) ? (string)$data[10] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C5_PREVID'
     * Предыдущий документ, удостоверяющий личность
     * @param array $data
     * @return array
     */
    protected function get_C5_PREVID(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'             => $data[0],
                'isPrevId'          => isset($data[1]) ? (string)$data[1] : null,
                'OKSM'              => isset($data[2]) ? (string)$data[2] : null,
                'otherCountry'      => isset($data[3]) ? (string)$data[3] : null,
                'idType'            => isset($data[4]) ? (string)$data[4] : null,
                'otherId'           => isset($data[5]) ? (string)$data[5] : null,
                'seriesNumber'      => isset($data[6]) ? (string)$data[6] : null,
                'idNum'             => isset($data[7]) ? (string)$data[7] : null,
                'issueDate'         => isset($data[8]) ? (string)$data[8] : null,
                'issueAuthority'    => isset($data[9]) ? (string)$data[9] : null,
                'divCode'           => isset($data[10]) ? (string)$data[10] : null,
                'validTo'           => isset($data[11]) ? (string)$data[11] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C6_REGNUM'
     * ИНН и налоговый режим (блок-константа)
     * @param array $data
     * @return array
     */
    protected function get_C6_REGNUM(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'         => $data[0],
                'taxpayerCode'  => isset($data[1]) ? (string)$data[1] : null,
                'taxpayerNum'   => isset($data[2]) ? (string)$data[2] : null,
                'regNum'        => isset($data[3]) ? (string)$data[3] : null,
                'spectaxCode'   => isset($data[4]) ? (string)$data[4] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C7_SNILS'
     * СНИЛС
     * @param array $data
     * @return array
     */
    protected function get_C7_SNILS(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'         => $data[0],
                'SNILS'         => isset($data[1]) ? (string)$data[1] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'DELETE'
     * Блок удаления
     * @param array $data
     * @return array
     */
    protected function get_DELETE(array $data) : array
    {
        return [
            'block' => $data[0],
        ];
    }

    /**
     * Получить блок 'C17_UID'
     * УИд сделки
     * @param array $data
     * @return array
     */
    protected function get_C17_UID(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'     => $data[0],
                'uuid'      => isset($data[1]) ? (string)$data[1] : null,
                'acctNum'   => isset($data[2]) ? (string)$data[2] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C18_TRADE'
     * Общие сведения о сделке
     * @param array $data
     * @return array
     */
    protected function get_C18_TRADE(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'                 => $data[0],
                'ownerIndic'            => isset($data[1]) ? (int)$data[1] : null,
                'openedDt'              => isset($data[2]) ? (string)$data[2] : null,
                'tradeTypeCode'         => isset($data[3]) ? (int)$data[3] : null,
                'loanKindCode'          => isset($data[4]) ? (int)$data[4] : null,
                'acctType'              => isset($data[5]) ? (int)$data[5] : null,
                'isConsumerLoan'        => isset($data[6]) ? (int)$data[6] : null,
                'hasCard'               => isset($data[7]) ? (int)$data[7] : null,
                'isNovation'            => isset($data[8]) ? (int)$data[8] : null,
                'isMoneySource'         => isset($data[9]) ? (int)$data[9] : null,
                'isMoneyBorrower'       => isset($data[10]) ? (int)$data[10] : null,
                'closeDt'               => isset($data[11]) ? (string)$data[11] : null,
                'lendertypeCode'        => isset($data[12]) ? (int)$data[12] : null,
                'obtainpartCred'        => isset($data[13]) ? (int)$data[13] : null,
                'creditLine'            => isset($data[14]) ? (int)$data[14] : null,
                'creditLineCode'        => isset($data[15]) ? (string)$data[15] : null,
                'interestrateFloat'     => isset($data[16]) ? (int)$data[16] : null,
                'transpartCred'         => isset($data[17]) ? (int)$data[17] : null,
                'transpartCredUuid'     => isset($data[18]) ? (string)$data[18] : null,
                'commitDate'            => isset($data[19]) ? (string)$data[19] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C19_ACCOUNTAMT'
     * Сумма и валюта обязательства
     * @param array $data
     * @return array
     */
    protected function get_C19_ACCOUNTAMT(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'             => $data[0],
                'creditLimit'       => isset($data[1]) ? strToFloat($data[1]) : null,
                'currencyCode'      => isset($data[2]) ? (string)$data[2] : null,
                'ensuredAmt'        => isset($data[3]) ? (string)$data[3] : null,
                'commitcurrCode'    => isset($data[4]) ? (string)$data[4] : null,
                'commitCode'        => isset($data[5]) ? (string)$data[5] : null,
                'amtDate'           => isset($data[6]) ? (string)$data[6] : null,
                'commitUuid'        => isset($data[7]) ? (string)$data[7] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C21_PAYMTCONDITION'
     * Сведения об условиях платежей, без учёта просрочки
     * @param array $data
     * @return array
     */
    protected function get_C21_PAYMTCONDITION(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'                 => $data[0],
                'principalTermsAmt'     => isset($data[1]) ? strToFloat($data[1]) : null,
                'principalTermsAmtDt'   => isset($data[2]) ? (string)$data[2] : null,
                'interestTermsAmt'      => isset($data[3]) ? strToFloat($data[3]) : null,
                'interestTermsAmtDt'    => isset($data[4]) ? (string)$data[4] : null,
                'termsFrequency'        => isset($data[5]) ? (int)$data[5] : null,
                'minPaymt'              => isset($data[6]) ? (string)$data[6] : null,
                'graceStartDt'          => isset($data[7]) ? (string)$data[7] : null,
                'graceEndDt'            => isset($data[8]) ? (string)$data[8] : null,
                'interestPaymentDueDate'=> isset($data[9]) ? (string)$data[9] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C22_OVERALLVAL'
     * Полная стоимость потребительского кредита (займа)
     * @param array $data
     * @return array
     */
    protected function get_C22_OVERALLVAL(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'                     => $data[0],
                'creditTotalAmt'            => isset($data[1]) ? (string)$data[1] : null,
                'creditTotalMonetaryAmt'    => isset($data[2]) ? (string)$data[2] : null,
                'creditTotalAmtDate'        => isset($data[3]) ? (string)$data[3] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C24_FUNDDATE'
     * Дата передачи финансирования субъекту или возникновения обеспечения исполнения обязательства
     * @param array $data
     * @return array
     */
    protected function get_C24_FUNDDATE(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'         => $data[0],
                'fundDate'      => isset($data[1]) ? (string)$data[1] : null,
                'trancheNum'    => isset($data[2]) ? (string)$data[2] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C25_ARREAR'
     * Сведения о задолженности
     * @param array $data
     * @return array
     */
    protected function get_C25_ARREAR(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'                 => $data[0],
                'isArrearExists'        => isset($data[1]) ? (int)$data[1] : null,
                'startAmtOutstanding'   => isset($data[2]) ? strToFloat($data[2]) : null,
                'lastPaymentDueCode'    => isset($data[3]) ? (string)$data[3] : null,
                'amtOutstanding'        => isset($data[4]) ? strToFloat($data[4]) : null,
                'principalOutstanding'  => isset($data[5]) ? strToFloat($data[5]) : null,
                'intOutstanding'        => isset($data[6]) ? strToFloat($data[6]) : null,
                'otherAmtOutstanding'   => isset($data[7]) ? strToFloat($data[7]) : null,
                'calcDate'              => isset($data[8]) ? (string)$data[8] : null,
                'unconfirmGrace'        => isset($data[9]) ? (string)$data[9] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C26_DUEARREAR'
     * Сведения о срочной задолженности
     * @param array $data
     * @return array
     */
    protected function get_C26_DUEARREAR(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'                 => $data[0],
                'startDt'               => isset($data[1]) ? (string)$data[1] : null,
                'lastPaymentDueCode'    => isset($data[2]) ? (int)$data[2] : null,
                'amtOutstanding'        => isset($data[3]) ? strToFloat($data[3]) : null,
                'principalOutstanding'  => isset($data[4]) ? strToFloat($data[4]) : null,
                'intOutstanding'        => isset($data[5]) ? strToFloat($data[5]) : null,
                'otherAmtOutstanding'   => isset($data[6]) ? strToFloat($data[6]) : null,
                'calcDate'              => isset($data[7]) ? (string)$data[7] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C27_PASTDUEARREAR'
     * Сведения о просроченной задолженности
     * @param array $data
     * @return array
     */
    protected function get_C27_PASTDUEARREAR(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'                 => $data[0],
                'pastDueDt'             => isset($data[1]) ? (string)$data[1] : null,
                'lastPaymentDueCode'    => isset($data[2]) ? (int)$data[2] : null,
                'amtPastDue'            => isset($data[3]) ? strToFloat($data[3]) : null,
                'principalAmtPastDue'   => isset($data[4]) ? strToFloat($data[4]) : null,
                'intAmtPastDue'         => isset($data[5]) ? strToFloat($data[5]) : null,
                'otherAmtPastDue'       => isset($data[6]) ? strToFloat($data[6]) : null,
                'calcDate'              => isset($data[7]) ? (string)$data[7] : null,
                'principalMissedDate'   => isset($data[8]) ? (string)$data[8] : null,
                'intMissedDate'         => isset($data[9]) ? (string)$data[9] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C28_PAYMT'
     * Сведения о внесении платежей
     * @param array $data
     * @return array
     */
    protected function get_C28_PAYMT(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'             => $data[0],
                'paymtDate'         => isset($data[1]) ? (string)$data[1] : null,
                'paymtAmt'          => isset($data[2]) ? strToFloat($data[2]) : null,
                'principalPaymtAmt' => isset($data[3]) ? strToFloat($data[3]) : null,
                'intPaymtAmt'       => isset($data[4]) ? strToFloat($data[4]) : null,
                'otherPaymtAmt'     => isset($data[5]) ? strToFloat($data[5]) : null,
                'totalAmt'          => isset($data[6]) ? strToFloat($data[6]) : null,
                'principalTotalAmt' => isset($data[7]) ? strToFloat($data[7]) : null,
                'intTotalAmt'       => isset($data[8]) ? strToFloat($data[8]) : null,
                'otherTotalAmt'     => isset($data[9]) ? strToFloat($data[9]) : null,
                'amtKeepCode'       => isset($data[10]) ? (int)$data[10] : null,
                'termsDueCode'      => isset($data[11]) ? (int)$data[11] : null,
                'daysPastDue'       => isset($data[12]) ? (int)$data[12] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C29_MONTHAVERPAYMT'
     * Величина среднемесячного платежа
     * @param array $data
     * @return array
     */
    protected function get_C29_MONTHAVERPAYMT(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'         => $data[0],
                'averPaymtAmt'  => isset($data[1]) ? strToFloat($data[1]) : null,
                'calcDate'      => isset($data[2]) ? (string)$data[2] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C38_OBLIGTERMINATION'
     * Сведения о прекращении обязательства (заполняется только при закрытии)
     * @param array $data
     * @return array
     */
    protected function get_C38_OBLIGTERMINATION(array $data) : array
    {
        $blockName = $data[0];
        
        try {
            $block[$blockName] = [
                'block'             => $data[0],
                'loanIndicator'     => isset($data[1]) ? (string)$data[1] : null,
                'loanIndicatorDt'   => isset($data[2]) ? (string)$data[2] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C54_OBLIGACCOUNT'
     * Сведения об учете обязательства
     * @param array $data
     * @return array
     */
    protected function get_C54_OBLIGACCOUNT(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'              => $data[0],
                'obligAccountCode'   => isset($data[1]) ? (string)$data[1] : null,
                'intRate'            => isset($data[2]) ? (string)$data[2] : null,
                'offbalanceAmt'      => isset($data[3]) ? (string)$data[3] : null,
                'preferenFinanc'     => isset($data[4]) ? (string)$data[4] : null,
                'preferenFinancInfo' => isset($data[5]) ? (string)$data[5] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    /**
     * Получить блок 'C56_OBLIGPARTTAKE'
     * Сведения об участии в обязательстве, по которому формируется КИ
     * @param array $data
     * @return array
     */
    protected function get_C56_OBLIGPARTTAKE(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'                 => $data[0],
                'flagIndicatorCode'     => isset($data[1]) ? (string)$data[1] : null,
                'approvedLoanTypeCode'  => isset($data[2]) ? (string)$data[2] : null,
                'agreementNumber'       => isset($data[3]) ? (string)$data[3] : null,
                'fundDt'                => isset($data[4]) ? (string)$data[4] : null,
                'defaultFlag'           => isset($data[5]) ? (string)$data[5] : null,
                'loanIndicator'         => isset($data[6]) ? (string)$data[6] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    protected function get_TRAILER(array $data) : array
    {
        $blockName = $data[0];

        try {
            $block[$blockName] = [
                'block'         => $data[0],
                'clientsCount'  => isset($data[1]) ? (string)$data[1] : null,
                'groupsCount'   => isset($data[2]) ? (string)$data[2] : null,
            ];
        } catch (\Throwable $e) {
            throw new \Error(__METHOD__ . "|" . $e->getMessage());
        }

        return $block;
    }

    public function getParsed() : array
    {
        return $this->nbkiData;
    }

    public function getErrors() : array
    {
        return $this->errors;
    }



}