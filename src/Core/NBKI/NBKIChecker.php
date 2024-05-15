<?php

namespace Core\NBKI;
use Core\NBKI\Checker\BlockSizeChecker;

require_once __DIR__ . "/../../libs/func.php";

class NBKIChecker
{
    protected array $parsed = [];
    protected array $errors = [];
    protected array $noCheckable = [];

    public function __construct(
        NBKIReport $report
    ) {
        $this->parsed = $report->getParsed();

        $this->serviceCheck();
    }

            protected function serviceCheck() : void
            {
                $this->blockSizeCheck();
            }

                    protected function blockSizeCheck() : self
                    {
                        foreach ($this->parsed['contracts'] as $contract) {
                            $contractGroupCounter =  $contract['groupCounter'];

                            foreach ($contract['blocks'] as $block) {
                                try{
                                    BlockSizeChecker::check($block);
                                } catch (\Throwable $e) {
                                    $this->errors[$contractGroupCounter][] = $e->getMessage();
                                    $this->noCheckable[] = $contractGroupCounter;
                                }
                            }
                        }

                        return $this;
                    }

    protected function C17_UID()
    {
        $blockName = getMethodName(__METHOD__);

        foreach ($this->parsed['contracts'] as $contract) {
            $groupCounter = $contract['groupsCount'];

           if (!array_key_exists($blockName, $contract)) {
               $this->errors[$groupCounter][] = "Отсутствует блок ({$blockName})";
           }
        }
    }

    public function C25_ARREAR() : self
    {
        $blockName = getMethodName(__METHOD__);

        foreach ($this->parsed['contracts'] as $contract) {
            try {
                $block = $contract['blocks'][$blockName] ??
                    throw new \Error("Отсутствует блок {$blockName}");

                $this->C25_ARREAR_CALC_SUM($block, $contract['groupCounter']);
                $this->C25_ARREAR_DIFF_OD_VS_LOAN($block, $contract['groupCounter']);
                $this->C25_ARREAR_DIFF_25_26_27($contract, $blockName);

            } catch (\Throwable $e) {
                $this->errors[ $contract['groupCounter'] ][] = $e->getMessage();
            }
        }

        return $this;
    }

            protected function C25_ARREAR_CALC_SUM(array $block, string $groupCounter) : void
            {
                try {
                    $blockName = $block['block'];

                    $amtOutstandingCalced = ROUND($block['principalOutstanding'] + $block['intOutstanding'] + $block['otherAmtOutstanding'], 2);

                    if ($block['amtOutstanding'] !== $amtOutstandingCalced) {
                        throw new \Error("{$blockName} | Сумма задолженности неверна (amtOutstanding): {$block['amtOutstanding']}/{$amtOutstandingCalced} (дано/расчёт)");
                    }

                } catch (\Throwable $e) {
                    $this->errors[ $groupCounter ][] = $e->getMessage();
                }
            }

            protected function C25_ARREAR_DIFF_OD_VS_LOAN(array $block, string $groupCounter) : void
            {
                try {
                    $blockName = $block['block'];

                    if ($block['principalOutstanding'] > $block['startAmtOutstanding']) {
                        $this->errors[ $groupCounter ][] =
                            "{$blockName} | Сумма задолженности по основному долгу больше Суммы выдачи (principalOutstanding > amtOutstanding): {$block['principalOutstanding']}/{$block['startAmtOutstanding']} (долг/выдача)";
                    }

                } catch (\Throwable $e) {
                    $this->errors[ $groupCounter ][] = $e->getMessage();
                }
            }

            protected function C25_ARREAR_DIFF_25_26_27(array $contract, string $blockName) : void
            {
                try {
                    if (!array_key_exists('C25_ARREAR', $contract['blocks']))
                        throw new \Error("Невозможно проверить сходимость блоков 25-26-27 (отсутствует блок C25_ARREAR");

                    if (!array_key_exists('C26_DUEARREAR', $contract['blocks']))
                        throw new \Error("Невозможно проверить сходимость блоков 25-26-27 (отсутствует блок C26_DUEARREAR");

                    if (!array_key_exists('C27_PASTDUEARREAR', $contract['blocks']))
                        throw new \Error("Невозможно проверить сходимость блоков 25-26-27 (отсутствует блок C27_PASTDUEARREAR");

                    $this->C25_ARREAR_DIFF_25_26_27_COSTALL($contract, $blockName);
                    $this->C25_ARREAR_DIFF_25_26_27_OD($contract, $blockName);
                    $this->C25_ARREAR_DIFF_25_26_27_OP($contract, $blockName);
                    $this->C25_ARREAR_DIFF_25_26_27_PENY($contract, $blockName);

                } catch (\Throwable $e) {
                    $this->errors[ $contract['groupCounter'] ][] = "{$blockName} | {$e->getMessage()}";
                }
            }

                    protected function C25_ARREAR_DIFF_25_26_27_COSTALL(array $contract, string $blockName) : void
                    {
                        try {
                            $block_C25 = $contract['blocks']['C25_ARREAR'];
                            $block_C26 = $contract['blocks']['C26_DUEARREAR'];
                            $block_C27 = $contract['blocks']['C27_PASTDUEARREAR'];

                            $amtOutstandingCalced = ROUND($block_C26['amtOutstanding'] + $block_C27['amtPastDue'], 2);

                            if ($block_C25['amtOutstanding'] !== $amtOutstandingCalced) {
                                throw new \Error("Сумма задолженности не совпадает с суммой блоков C26_DUEARREAR и C27_PASTDUEARREAR (amtOutstanding): {$block_C25['amtOutstanding']}/{$amtOutstandingCalced} (дано/расчёт)");
                            }

                        } catch (\Throwable $e) {
                            $this->errors[ $contract['groupCounter'] ][] = "{$blockName} | {$e->getMessage()}";
                        }
                    }

                    protected function C25_ARREAR_DIFF_25_26_27_OD(array $contract, string $blockName) : void
                    {
                        try {
                            $block_C25 = $contract['blocks']['C25_ARREAR'];
                            $block_C26 = $contract['blocks']['C26_DUEARREAR'];
                            $block_C27 = $contract['blocks']['C27_PASTDUEARREAR'];

                            $principalOutstandingCalced = ROUND($block_C26['principalOutstanding'] + $block_C27['principalAmtPastDue'], 2);

                            if ($block_C25['principalOutstanding'] !== $principalOutstandingCalced) {
                                throw new \Error("Сумма задолженности ОД не совпадает с суммой блоков C26_DUEARREAR и C27_PASTDUEARREAR (principalOutstanding): {$block_C25['principalOutstanding']}/{$principalOutstandingCalced} (дано/расчёт)");
                            }

                        } catch (\Throwable $e) {
                            $this->errors[ $contract['groupCounter'] ][] = "{$blockName} | {$e->getMessage()}";
                        }
                    }

                    protected function C25_ARREAR_DIFF_25_26_27_OP(array $contract, string $blockName) : void
                    {
                        try {
                            $block_C25 = $contract['blocks']['C25_ARREAR'];
                            $block_C26 = $contract['blocks']['C26_DUEARREAR'];
                            $block_C27 = $contract['blocks']['C27_PASTDUEARREAR'];

                            $intOutstandingCalced = ROUND($block_C26['intOutstanding'] + $block_C27['intAmtPastDue'], 2);

                            if ($block_C25['intOutstanding'] !== $intOutstandingCalced) {
                                throw new \Error("Сумма задолженности ОП не совпадает с суммой блоков C26_DUEARREAR и C27_PASTDUEARREAR (intOutstanding): {$block_C25['intOutstanding']}/{$intOutstandingCalced} (дано/расчёт)");
                            }

                        } catch (\Throwable $e) {
                            $this->errors[ $contract['groupCounter'] ][] = "{$blockName} | {$e->getMessage()}";
                        }
                    }

                    protected function C25_ARREAR_DIFF_25_26_27_PENY(array $contract, string $blockName) : void
                    {
                        try {
                            $block_C25 = $contract['blocks']['C25_ARREAR'];
                            $block_C26 = $contract['blocks']['C26_DUEARREAR'];
                            $block_C27 = $contract['blocks']['C27_PASTDUEARREAR'];

                            $otherAmtOutstandingCalced = ROUND($block_C26['otherAmtOutstanding'] + $block_C27['otherAmtPastDue'], 2);

                            if ($block_C25['otherAmtOutstanding'] !== $otherAmtOutstandingCalced) {
                                throw new \Error("Сумма задолженности ПЕНИ не совпадает с суммой блоков C26_DUEARREAR и C27_PASTDUEARREAR (otherAmtOutstanding): {$block_C25['otherAmtOutstanding']}/{$otherAmtOutstandingCalced} (дано/расчёт)");
                            }

                        } catch (\Throwable $e) {
                            $this->errors[ $contract['groupCounter'] ][] = "{$blockName} | {$e->getMessage()}";
                        }
                    }

    public function C26_DUEARREAR() : self
    {
        $blockName = getMethodName(__METHOD__);

        foreach ($this->parsed['contracts'] as $contract) {
            try {
                $block = $contract['blocks'][$blockName] ??
                    throw new \Error("Отсутствует блок");

                $amtOutstandingCalced = ROUND($block['principalOutstanding'] + $block['intOutstanding'], 2);

                if ($block['amtOutstanding'] !== $amtOutstandingCalced) {
                    throw new \Error("Сумма срочной задолженности (сроч. остаток ОД+ОП) неверна (amtOutstanding): {$block['amtOutstanding']}/{$amtOutstandingCalced} (дано/расчёт)");
                }
            } catch (\Throwable $e) {
                $this->errors[ $contract['groupCounter'] ][] = "{$blockName} | {$e->getMessage()}";
            }
        }

        return $this;
    }

    public function C27_PASTDUEARREAR() : self
    {
        $blockName = getMethodName(__METHOD__);

        foreach ($this->parsed['contracts'] as $contract) {
            try {
                $block = $contract['blocks'][$blockName] ??
                    throw new \Error("Отсутствует блок");

                $amtPastDueCalced = ROUND($block['principalAmtPastDue'] + $block['intAmtPastDue'] + $block['otherAmtPastDue'], 2);

                if ($block['amtPastDue'] !== $amtPastDueCalced) {
                    throw new \Error("Сумма просроченной задолженности неверна (amtPastDue): {$block['amtPastDue']}/{$amtPastDueCalced} (дано/расчёт)");
                }
            } catch (\Throwable $e) {
                $this->errors[ $contract['groupCounter'] ][] = "{$blockName} | {$e->getMessage()}";
            }
        }

        return $this;
    }

    public function C28_PAYMT() : self
    {
        $blockName = getMethodName(__METHOD__);

        foreach ($this->parsed['contracts'] as $contract) {
            try {
                $block = $contract['blocks'][$blockName] ??
                    throw new \Error("Отсутствует блок {$blockName}");

                $this->C28_PAYMT_CALC_LAST_PAY_SUM($block, $contract['groupCounter']);
                $this->C28_PAYMT_CALC_PAYS_SUM($block, $contract['groupCounter']);
                $this->C28_PAYMT_SHTRAF_DAYS($contract, $blockName);

            } catch (\Throwable $e) {
                $this->errors[ $contract['groupCounter'] ][] = $e->getMessage();
            }
        }

        return $this;
    }

            protected function C28_PAYMT_CALC_LAST_PAY_SUM(array $block, string $groupCounter) : void
            {
                try {
                    $blockName = $block['block'];

                    $paymtAmtCalced = ROUND($block['principalPaymtAmt'] + $block['intPaymtAmt'] + $block['otherPaymtAmt'], 2);

                    if ($block['paymtAmt'] !== $paymtAmtCalced) {
                        throw new \Error("{$blockName} | Сумма последнего внесенного платежа неверна (paymtAmt): {$block['paymtAmt']}/{$paymtAmtCalced} (дано/расчёт)");
                    }

                } catch (\Throwable $e) {
                    $this->errors[ $groupCounter ][] = $e->getMessage();
                }
            }

            protected function C28_PAYMT_CALC_PAYS_SUM(array $block, string $groupCounter) : void
            {
                try {
                    $blockName = $block['block'];

                    $totalAmtCalced = ROUND($block['principalTotalAmt'] + $block['intTotalAmt'] + $block['otherTotalAmt'], 2);

                    if ($block['totalAmt'] !== $totalAmtCalced) {
                        throw new \Error("{$blockName} | Сумма всех внесенных платежей неверна (totalAmt): {$block['totalAmt']}/{$totalAmtCalced} (дано/расчёт)");
                    }

                } catch (\Throwable $e) {
                    $this->errors[ $groupCounter ][] = $e->getMessage();
                }
            }

            protected function C28_PAYMT_SHTRAF_DAYS(array $contract, string $blockName) : void
            {
                try {
                    if (!array_key_exists('0_GROUPHEADER', $contract['blocks']))
                        throw new \Error("Невозможно проверить штрафные дни (отсутствует блок 0_GROUPHEADER");

                    if (!array_key_exists('C27_PASTDUEARREAR', $contract['blocks']))
                        throw new \Error("Невозможно проверить штрафные дни (отсутствует блок C27_PASTDUEARREAR");

                    $block_GROUPHEADER = $contract['blocks']['0_GROUPHEADER'];
                    $block_C27 = $contract['blocks']['C27_PASTDUEARREAR'];
                    $block_C28 = $contract['blocks']['C28_PAYMT'];

                    $isClosed = $block_GROUPHEADER['eventNumber'] === '2.5';

                    if ($isClosed && ($block_C28['daysPastDue'] !== 0)) {
                        throw new \Error("{$blockName} | Число штрафных дней неверно при закрытии займа (daysPastDue): {$block_C28['daysPastDue']}/0 (дано/надо)");
                    }

                    $daysPastAfterShtrafBegin = datesDiffBothInclude($block_C27['calcDate'], $block_C27['pastDueDt']);

                    if (!$isClosed && ($block_C28['daysPastDue'] !== $daysPastAfterShtrafBegin)) {
                        throw new \Error("{$blockName} | Число штрафных дней неверно (daysPastDue): {$block_C28['daysPastDue']}/{$daysPastAfterShtrafBegin} (дано/расчёт)");
                    }

                } catch (\Throwable $e) {
                    $this->errors[ $contract['groupCounter']  ][] = $e->getMessage();
                }
            }

    public function C29_MONTHAVERPAYMT() : self
    {
        $blockName = getMethodName(__METHOD__);

        foreach ($this->parsed['contracts'] as $contract) {
            try {
                $block_C29 = $contract['blocks'][$blockName] ??
                    throw new \Error("Отсутствует блок");

                $block_C25 = $contract['blocks']['C25_ARREAR'] ??
                    throw new \Error("Отсутствует необходимый блок (C25_ARREAR)");

                $averPaymtAmtCalced = ROUND($block_C25['amtOutstanding']);

                if ($block_C29['averPaymtAmt'] > $averPaymtAmtCalced) {
                    throw new \Error("Величина среднемесячного платежа больше Суммы задолженности C25_ARREAR (averPaymtAmt): {$block_C29['averPaymtAmt']}/{$averPaymtAmtCalced} (дано/расчёт)");
                }
            } catch (\Throwable $e) {
                $this->errors[ $contract['groupCounter'] ][] = "{$blockName} | {$e->getMessage()}";
            }
        }

        return $this;
    }

    public function getErrors() : array
    {
        return $this->errors;
    }

    /*
    protected function getBlockExistContracts(string $blockName) : array
    {
        $contracts = [];

        foreach ($this->parsed['contracts'] as $contract) {
            $contractGroupCounter = $contract['groupCounter'];

            if (array_key_exists($blockName, $contract['blocks'])) {
                $contracts[] = $contractGroupCounter;
            } else {
                $this->errors[$contractGroupCounter][] = "Отсутствует блок {$blockName}";
            }
        }

        return $contracts;
    }*/

    /*
    public function C27_PASTDUEARREAR() : void
    {
        $blockName = getMethodName(__METHOD__);

        foreach ($this->parsed['contracts'] as $contract) {
            $contractGroupCounter =  $contract['groupCounter'];

            try {
                $block = $contract['blocks'][$blockName] ?? throw new \Error("Отсутствует блок {$blockName}");
            } catch (\Throwable $e) {
                $this->errors[$contractGroupCounter][] = $e->getMessage();
            }
        }
    }
    */
}