<?php

/**
 * 
 * Ces codes ne s'éxécutent pas correctement ; soit une erreur est lancé par
 * PHP, soit il manque quelque chose à la logique
 *
 */

class Cochon
{
    private $prenom;
    protected $sauvage;
    public function __construct($prenom)
    {
        $this->prenom = $prenom;
        $this->sauvage = false;
    }

    public function isSauvage()
    {
        return $this->sauvage;
    }
}

class Sanglier extends Cochon
{
    public function __construct($prenom)
    {
        $this->sauvage = true;
        parent::__construct($prenom);
    }
}

$Robert = new Sanglier('Robert');

assert($Robert->isSauvage());

/**
 * -----------------------------------------------------------------------------
 */

$connection = new PDO('sqlite::memory:');

$connection->beginTransaction();

try {

    $sql = 'INSERT INTO usr (id, nom, prenom, created_on)
            VALUES (:id, :nom, :prenom, NOW())';

    $stmt = $connection->prepare($sql);

    $n = 0;

    while ($n++ < 100) {
        $stmt->execute(array(
            ':id'     => $n,
            ':nom'    => 'jean',
            ':prenom' => 'bonno',
        ));
    }

} catch (\PDOException $e) {
    $connection->rollBack();
}

