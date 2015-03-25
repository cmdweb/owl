<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 16/02/2015
 * Time: 00:58
 */

namespace Alcatraz\Owl;


interface iOwl {
    public function FirstOrDefault();

    public function ToList();

    public function OrderBy($campo);

    public function OrderByDescending($campo);

    public function Take($take);

    public function Skip($skip);

    public function Select($select, $novaClasse = null);

    public function Join($join, $on, $on2);

    public function LeftJoin($join, $on, $on2);

    public function Where($where);

    public function GroupBy($fields);

    public function Sum($field);

    public function AVG($field);

    public function Having($having);

    public function Distinct();

    public function Count();
} 