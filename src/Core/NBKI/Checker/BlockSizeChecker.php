<?php

namespace Core\NBKI\Checker;

class BlockSizeChecker
{
    protected const array BLOCK_SIZES = [
            'HEADER'                => 12,
            '0_GROUPHEADER'         => 5,
            'C1_NAME'               => 3,
            'C2_PREVNAME'           => 5,
            'C3_BIRTH'              => 3,
            'C4_ID'                 => 10,
            'C5_PREVID'             => 11,
            'C6_REGNUM'             => 4,
            'C7_SNILS'              => 1,
            'C17_UID'               => 2,
            'C18_TRADE'             => 19,
            'C19_ACCOUNTAMT'        => 7,
            'C21_PAYMTCONDITION'    => 9,
            'C22_OVERALLVAL'        => 3,
            'C24_FUNDDATE'          => 2,
            'C25_ARREAR'            => 9,
            'C26_DUEARREAR'         => 7,
            'C27_PASTDUEARREAR'     => 9,
            'C28_PAYMT'             => 12,
            'C29_MONTHAVERPAYMT'    => 2,
            'C38_OBLIGTERMINATION'  => 2,
            'C54_OBLIGACCOUNT'      => 5,
            'C56_OBLIGPARTTAKE'     => 6,
            'TRAILER'               => 2,
            'DELETE'                => 0,
    ];
    protected const int DUMMY_BLOCK_NAME_FIELD = 1;


    public static function check(array $block) : void
    {
        $blockName = $block['block'];
        $blockSize = self::getBlockSize($block) - self::DUMMY_BLOCK_NAME_FIELD;

        self::checkBlockName($blockName);
        self::checkBlockSize($blockName, $blockSize);
    }

            protected static function getBlockSize(array $block) : int
            {
                return count(array_filter(
                    $block,
                    fn($field) => $field !== null
                ));
            }

    protected static function checkBlockName(string $blockName) : void
    {
        if (!array_key_exists($blockName, self::BLOCK_SIZES)) {
            throw new \Error("Неизвестное поле {$blockName}");
        }
    }

    protected static function checkBlockSize(string $blockName, int $blockSize) : void
    {
        $blockSizeStd = self::BLOCK_SIZES[$blockName];

        if ($blockSize !== $blockSizeStd) {
            throw new \Error("{$blockName} | Несоответствует размеру: {$blockSize}/{$blockSizeStd} (дано/нужно)");
        }
    }
}