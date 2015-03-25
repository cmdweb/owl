<?php
/**
 * Created by PhpStorm.
 * User: gabriel.malaquias
 * Date: 05/01/2015
 * Time: 10:22
 */

namespace Alcatraz\Owl;

use Alcatraz\ModelState\ModelState;
use Alcatraz\Kernel\Database;

class Owl extends Database {

    /**
     * Guarda se a transação ja foi aberta ou não
     */
    private $transactionActive = false;

    /**
     * Construtor
     */
    function __construct(){
        parent::__construct();
        return $this;
    }

    /**
     * @param $type
     * @param null $where
     * @param null $persist
     * @return OwlSelect
     */
    function Get($type, $where = null, $persist = null){
        return new OwlSelect($type, $where, $persist, $this);
    }

    /**
     * Seleciona pelo Id
     * @param $type
     * @param $id
     * @throws OwlException
     */
    function GetById($type, $id){
        $newClass = NAMESPACE_ENTITIES . $type;
        if(class_exists ($newClass)) {
            $class = new $newClass();
            $primaryKey = ModelState::GetPrimary($class);
            if ($primaryKey != null)
                return $this->Get($type, $primaryKey . '=' . $id)->FirstOrDefault();

            return null;
        }

        throw new OwlException("Classe {$newClass} não encontrada", 1);
    }

    /**
     * Insere dados no banco atraves de uma model mapeada
     * @param $model
     * @return string
     */
    function Insert($model){

        $this->OpenTransaction();

        $data = clone $model;

        $table = $this->getTableName($data);

        //verifico se é um objeto
        if (is_object($data))
            ModelState::ModelTreatment($data);

        $data = (array)$data;


        // Ordena
        ksort($data);

        // Campos e valores
        $camposNomes = implode('`, `', array_keys($data));
        $camposValores = ':' . implode(', :', array_keys($data));


        // Prepara a Query
        $sth = $this->prepare("INSERT INTO $table (`$camposNomes`) VALUES ($camposValores)");

        // Define os dados
        foreach ($data as $key => $value) {
            // Se o tipo do dado for inteiro, usa PDO::PARAM_INT, caso contr�rio, PDO::PARAM_STR
            $tipo = (is_int($value)) ? \PDO::PARAM_INT : \PDO::PARAM_STR;

            // Define o dado
            $sth->bindValue(":$key", $value, $tipo);
        }

        // Executa
        try {
            $sth->execute();
        }catch (\Exception $e){
            echo $e->getMessage();
        }

        $primaryKey = ModelState::GetPrimary($model);
        if(!empty($primaryKey))
            $model->$primaryKey = $this->lastInsertId();
        return $this->lastInsertId();
    }

    /**
     * Atualiza os dados no banco atraves de uma model mapeada
     * @param $model Objeto que contem os dados
     * @param $campos Seleciona os campos que quer atulizar passando por array, caso seja nulo serão atualizados todos os campos
     */
    public function Update($model, array $campos = null)
    {
        $this->OpenTransaction();
        
        $data = clone $model;

        if (is_object($data))
            ModelState::ModelTreatment($data);

        $primaryKey = ModelState::GetPrimary($model);
        if($primaryKey == null)
            throw new OwlException("Classe nao contem PK");

        $table = $this->getTableName($data);

        $data = (array)$data;

        $novosDados = NULL;

        if($campos == null)
            $campos = array_keys($data);

        foreach ($campos as $key) {
            $novosDados .= "`$key`=:$key,";
        }

        $novosDados = rtrim($novosDados, ',');

        // Prepara a Query
        $sth = $this->prepare("UPDATE $table SET $novosDados WHERE $primaryKey = '".$model->$primaryKey."'");

        // Define os dados
        foreach ($campos as $key) {
            // Se o tipo do dado for inteiro, usa PDO::PARAM_INT, caso contr�rio, PDO::PARAM_STR
            $tipo = (is_int($model->$key)) ? \PDO::PARAM_INT : \PDO::PARAM_STR;

            // Define o dado
            $sth->bindValue(":$key",$model->$key, $tipo);
        }

        try {
            $sth->execute();
            $model = $this->GetById($table,$model->$primaryKey);
        }catch (\Exception $e){
            echo $e->getMessage();
        }
    }

    /**
     * Deleta os dados no banco atraves de uma model mapeada
     * @param $model
     * @return int
     */
    public function Delete($model)
    {
        $this->OpenTransaction();

        $primaryKey = ModelState::GetPrimary($model);
        $table = $this->getTableName($model);

        if($primaryKey != null) {
            $exec = $this->prepare("DELETE FROM " . $table . " WHERE " . $primaryKey . " = " . $model->$primaryKey . " LIMIT 1");
            $exec->execute();
            if($exec->rowCount() > 0)
                return true;

            throw new OwlException("A execução atingiu um numero 0 de linhas");
        }
        return false;
    }

    /**
     * Finaliza a transação com o banco e commita as alterações
     * @return bool
     */
    public function Save(){
        if($this->transactionActive) {
            try {
                $this->commit();
                $this->exec("SET FOREIGN_KEY_CHECKS = 1;");
            } catch (\Exception $e) {
                $this->rollBack();
                echo $e->getMessage();
            }

            $this->transactionActive = false;
        }

        return false;
    }

    /**
     * Abre a transaçao com o banco caso não esteja aberta
     */
    private function OpenTransaction(){
        if(!$this->transactionActive) {
            $this->beginTransaction();
            $this->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $this->transactionActive = true;
        }
    }

    /**
     * Pega o nome da tabela atraves do nome da Classe
     * @param $model
     * @return mixed
     */
    private function getTableName($model){
        return str_replace(NAMESPACE_ENTITIES , "",  get_class($model));
    }

    /**
     * Destroi a classe
     */
    function __destruct(){
        unset($this);
    }
}