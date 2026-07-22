<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_converte_valores_para_centavos(): void
    {
        $this->assertSame(
            10000,
            Money::toCents('100')
        );

        $this->assertSame(
            10050,
            Money::toCents('100.5')
        );

        $this->assertSame(
            10050,
            Money::toCents('100.50')
        );
    }

    public function test_formata_centavos_com_duas_casas(): void
    {
        $this->assertSame(
            '100.50',
            Money::format(10050)
        );

        $this->assertSame(
            '0.01',
            Money::format(1)
        );
    }

    public function test_rejeita_mais_de_duas_casas_decimais(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::toCents('10.999');
    }

    public function test_rejeita_valor_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::toCents('0');
    }
}