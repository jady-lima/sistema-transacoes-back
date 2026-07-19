<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    private const MONEY_PATTERN = '/^\d{1,12}(?:\.\d{1,2})?$/';

    /**
     * Recebe o valor da quantia vindo da api como string e converte em centavos
     */
    public static function toCents(string $amount): int
    {
        $amount = trim($amount);

        if (!preg_match(self::MONEY_PATTERN, $amount)) {
            throw new InvalidArgumentException(
                'O valor deve ser uma string decimal positiva com duas casas decimais.'
            );
        }

        [$integerPart, $decimalPart] = array_pad(
            explode('.', $amount, 2),
            2,
            ''
        );

        $decimalPart = str_pad($decimalPart, 2, '0');

        $cents = ((int) $integerPart * 100) + (int) $decimalPart;

        if ($cents < 0) {
            throw new InvalidArgumentException(
                'O valor precisa ser maior ou igual a zero.'
            );
        }

        return $cents;
    }

    /**
     * Formata o valor de centavos para reais
     */
    public static function format(int $cents): string
    {
        $integerPart = intdiv($cents, 100);
        $decimalPart = abs($cents % 100);

        return sprintf('%d.%02d', $integerPart, $decimalPart);
    }
}