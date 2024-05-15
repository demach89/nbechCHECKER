<?php

function getMethodName(string $namespacedMethod) : string
{
    return array_reverse(
        explode('::', $namespacedMethod)
    )[0];
}

function strToFloat(string $strDgt) :  float
{
    return (float)str_replace(',', '.', $strDgt);
}

function datesDiffBothInclude(string $date1, string $date2) : int
{
    try {
        $dateObj1 = new DateTime($date1);
        $dateObj2 = new DateTime($date2);

        return ($dateObj1->diff($dateObj2)->days) + 1; // Включая пограничные даты
    } catch (\Throwable $e) {
        throw new \Error("Неверный формат даты" . $e->getMessage());
    }
}