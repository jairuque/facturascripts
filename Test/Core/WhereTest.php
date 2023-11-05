<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Core;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Where;
use PHPUnit\Framework\TestCase;

final class WhereTest extends TestCase
{
    /** @var DataBase */
    private $db;

    public function testNewInstance(): void
    {
        $item = new Where('test', 'value');
        $this->assertEquals('test', $item->fields);
        $this->assertEquals('value', $item->value);
        $this->assertEquals('=', $item->operator);
        $this->assertEquals('AND', $item->operation);

        $sql = $this->db()->escapeColumn('test') . ' = ' . $this->db()->var2str('value');
        $this->assertEquals($sql, $item->sql());
    }

    public function testColumn(): void
    {
        $item = Where::column('test2', 'value2');
        $this->assertEquals('test2', $item->fields);
        $this->assertEquals('value2', $item->value);
        $this->assertEquals('=', $item->operator);
        $this->assertEquals('AND', $item->operation);

        $sql = $this->db()->escapeColumn('test2') . ' = ' . $this->db()->var2str('value2');
        $this->assertEquals($sql, $item->sql());
    }

    public function testColumnDate(): void
    {
        $date = '02-02-2020';
        $item = Where::column('test3', $date);
        $this->assertEquals('test3', $item->fields);
        $this->assertEquals($date, $item->value);
        $this->assertEquals('=', $item->operator);
        $this->assertEquals('AND', $item->operation);

        $sql = $this->db()->escapeColumn('test3') . ' = ' . $this->db()->var2str($date);
        $this->assertEquals($sql, $item->sql());
    }

    public function testColumnEqField(): void
    {
        $item = Where::lt('disponible', 'field:cantidad');
        $this->assertEquals('disponible', $item->fields);
        $this->assertEquals('field:cantidad', $item->value);
        $this->assertEquals('<', $item->operator);
        $this->assertEquals('AND', $item->operation);

        $sql = $this->db()->escapeColumn('disponible') . ' < ' . $this->db()->escapeColumn('cantidad');
        $this->assertEquals($sql, $item->sql());
    }

    public function testMultiColumn(): void
    {
        $item = Where::column('col1|col2|col3', 'value');
        $this->assertEquals('col1|col2|col3', $item->fields);
        $this->assertEquals('value', $item->value);
        $this->assertEquals('=', $item->operator);
        $this->assertEquals('AND', $item->operation);

        $sql = '(' . $this->db()->escapeColumn('col1') . ' = ' . $this->db()->var2str('value')
            . ' OR ' . $this->db()->escapeColumn('col2') . ' = ' . $this->db()->var2str('value')
            . ' OR ' . $this->db()->escapeColumn('col3') . ' = ' . $this->db()->var2str('value') . ')';
        $this->assertEquals($sql, $item->sql());
    }

    public function testColumnCastInteger(): void
    {
        $item = Where::gt('integer:codigo', 100);
        $this->assertEquals('integer:codigo', $item->fields);
        $this->assertEquals(100, $item->value);
        $this->assertEquals('>', $item->operator);
        $this->assertEquals('AND', $item->operation);

        $sql = $this->db()->castInteger('codigo') . ' > ' . $this->db()->var2str(100);
        $this->assertEquals($sql, $item->sql());
    }

    public function testWhereBetween(): void
    {
        $item = Where::between('test', 'value1', 'value2');
        $this->assertEquals('test', $item->fields);
        $this->assertEquals(['value1', 'value2'], $item->value);
        $this->assertEquals('BETWEEN', $item->operator);
        $this->assertEquals('AND', $item->operation);

        $sql = $this->db()->escapeColumn('test')
            . ' BETWEEN ' . $this->db()->var2str('value1')
            . ' AND ' . $this->db()->var2str('value2');
        $this->assertEquals($sql, $item->sql());
    }

    public function testWhereIn(): void
    {
        // pasamos los valore como array
        $item = Where::in('test', ['value1', 'value2']);
        $this->assertEquals('test', $item->fields);
        $this->assertEquals(['value1', 'value2'], $item->value);
        $this->assertEquals('IN', $item->operator);
        $this->assertEquals('AND', $item->operation);

        $sql = $this->db()->escapeColumn('test')
            . ' IN (' . $this->db()->var2str('value1')
            . ', ' . $this->db()->var2str('value2') . ')';
        $this->assertEquals($sql, $item->sql());

        // pasamos los valore como string
        $item2 = Where::in('test2', 'value3,value4, value5');
        $this->assertEquals('test2', $item2->fields);
        $this->assertEquals('value3,value4, value5', $item2->value);
        $this->assertEquals('IN', $item2->operator);
        $this->assertEquals('AND', $item2->operation);

        $sql2 = $this->db()->escapeColumn('test2')
            . ' IN (' . $this->db()->var2str('value3')
            . ', ' . $this->db()->var2str('value4')
            . ', ' . $this->db()->var2str('value5') . ')';
        $this->assertEquals($sql2, $item2->sql());

        // pasamos una consulta select
        $item3 = Where::in('test3', 'SELECT col1 FROM test_table');
        $this->assertEquals('test3', $item3->fields);
        $this->assertEquals('SELECT col1 FROM test_table', $item3->value);
        $this->assertEquals('IN', $item3->operator);
        $this->assertEquals('AND', $item3->operation);

        $sql3 = $this->db()->escapeColumn('test3')
            . ' IN (SELECT col1 FROM test_table)';
        $this->assertEquals($sql3, $item3->sql());
    }

    public function testWhereLike(): void
    {
        // sin comodines
        $item = Where::like('test', 'value');
        $this->assertEquals('test', $item->fields);
        $this->assertEquals('value', $item->value);
        $this->assertEquals('LIKE', $item->operator);
        $this->assertEquals('AND', $item->operation);

        $sql = 'LOWER(' . $this->db()->escapeColumn('test')
            . ") LIKE LOWER('%" . $this->db()->escapeString('value') . "%')";
        $this->assertEquals($sql, $item->sql());

        // con comodín al principio
        $item2 = Where::like('test2', '%value2');
        $sql2 = 'LOWER(' . $this->db()->escapeColumn('test2')
            . ") LIKE LOWER('%" . $this->db()->escapeString('value2') . "')";
        $this->assertEquals($sql2, $item2->sql());

        // con comodín al final
        $item3 = Where::like('test3', 'value3%');
        $sql3 = 'LOWER(' . $this->db()->escapeColumn('test3')
            . ") LIKE LOWER('" . $this->db()->escapeString('value3') . "%')";
        $this->assertEquals($sql3, $item3->sql());

        // con comodín al principio y al final
        $item4 = Where::like('test4', '%value4%');
        $sql4 = 'LOWER(' . $this->db()->escapeColumn('test4')
            . ") LIKE LOWER('%" . $this->db()->escapeString('value4') . "%')";
        $this->assertEquals($sql4, $item4->sql());

        // con comodín en medio
        $item5 = Where::like('test5', 'value5%value5');
        $sql5 = 'LOWER(' . $this->db()->escapeColumn('test5')
            . ") LIKE LOWER('" . $this->db()->escapeString('value5%value5') . "')";
        $this->assertEquals($sql5, $item5->sql());
    }

    public function testMultiAnd(): void
    {
        // hacemos una consulta por nombre = 'test' y total > 100
        $where = [
            Where::column('nombre', 'test'),
            Where::gt('total', 100)
        ];

        $sql = $this->db()->escapeColumn('nombre') . ' = ' . $this->db()->var2str('test')
            . ' AND ' . $this->db()->escapeColumn('total') . ' > ' . $this->db()->var2str(100);
        $this->assertEquals($sql, Where::multiSql($where));

        // incluimos una comprobación de fecha entre 01-01-2020 y 31-01-2020
        $where[] = Where::between('fecha', '01-01-2020', '31-01-2020');

        $sql .= ' AND ' . $this->db()->escapeColumn('fecha') . ' BETWEEN '
            . $this->db()->var2str('01-01-2020') . ' AND ' . $this->db()->var2str('31-01-2020');
        $this->assertEquals($sql, Where::multiSql($where));
    }

    public function testMultiOr(): void
    {
        // hacemos una consulta por nombre != 'test' o total <= 100
        $where = [
            Where::notEq('nombre', 'test'),
            Where::orLte('total', 100)
        ];

        $sql = $this->db()->escapeColumn('nombre') . ' != ' . $this->db()->var2str('test')
            . ' OR ' . $this->db()->escapeColumn('total') . ' <= ' . $this->db()->var2str(100);
        $this->assertEquals($sql, Where::multiSql($where));

        // añadimos una comprobación de OR nick IS NULL
        $where[] = Where::orIsNull('nick');

        $sql .= ' OR ' . $this->db()->escapeColumn('nick') . ' IS NULL';
        $this->assertEquals($sql, Where::multiSql($where));
    }

    public function testSub(): void
    {
        // hacemos la consulta (nombre = 'test2' OR nombre = 'test3') AND total >= 100
        $where = [
            Where::sub([
                Where::eq('nombre', 'test2'),
                Where::orEq('nombre', 'test3')
            ]),
            Where::gte('total', 100)
        ];

        $sql = '(' . $this->db()->escapeColumn('nombre') . ' = ' . $this->db()->var2str('test2')
            . ' OR ' . $this->db()->escapeColumn('nombre') . ' = ' . $this->db()->var2str('test3') . ')'
            . ' AND ' . $this->db()->escapeColumn('total') . ' >= ' . $this->db()->var2str(100);
        $this->assertEquals($sql, Where::multiSql($where));
    }

    private function db(): DataBase
    {
        if (null === $this->db) {
            $this->db = new DataBase();
        }

        return $this->db;
    }
}
