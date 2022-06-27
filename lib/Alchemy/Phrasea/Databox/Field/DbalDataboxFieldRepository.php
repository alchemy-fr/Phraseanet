<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox\Field;

use Doctrine\DBAL\Connection;

final class DbalDataboxFieldRepository implements DataboxFieldRepository
{
    private static $columnNames = [
        'id',
        'thumbtitle',
        'separator',
        'dces_element',
        'tbranch',
        'type',
        'report',
        'multi',
        'required',
        'readonly',
        'indexable',
        'name',
        'src',
        'business',
        'aggregable',
        'VocabularyControlType',
        'RestrictToVocabularyControl',
        'sorter',
        'label_en',
        'label_fr',
        'label_de',
        'label_nl',
        'generate_cterms',
        'gui_editable',
        'gui_visible',
        'printable',
    ];

    /** @var DataboxFieldFactory */
    private $factory;
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection, DataboxFieldFactory $factory)
    {
        $this->connection = $connection;
        $this->factory = $factory;
    }

    public function find($id)
    {
        $row = $this->fetchRow($id);

        if (is_array($row)) {
            return $this->factory->create($row);
        }

        return null;
    }

    public function findAll()
    {
        return $this->factory->createMany($this->fetchRows());
    }

    /**
     * @param int $id
     * @return false|array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function fetchRow($id)
    {
        $result = $this->connection->executeQuery($this->getSQLForSingleRow(), ['id' => $id]);
        $row = $result->fetch(\PDO::FETCH_ASSOC);
        $result->closeCursor();

        return $row;
    }

    private function fetchRows()
    {
        $statement = $this->connection->executeQuery($this->getSQLForAllRows());
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $rows;
    }

    /**
     * @return string
     */
    private function getSQLForSingleRow()
    {
        static $sql;

        if (!$sql) {
            $sql = $this->connection->createQueryBuilder()
                ->select($this->getQuotedFields())
                ->from('metadatas_structure', 's')
                ->where('id = :id')
                ->getSQL();
        }

        return $sql;
    }

    /**
     * @return string
     */
    private function getSQLForAllRows()
    {
        static $sql;

        if (!$sql) {
            $sql = $this->connection->createQueryBuilder()
                ->select($this->getQuotedFields())
                ->from('metadatas_structure', 's')
                ->orderBy('sorter')
                ->getSQL();
        }

        return $sql;
    }

    /**
     * @return array
     */
    private function getQuotedFields()
    {
        $fields = [];

        foreach (self::$columnNames as $field) {
            $fields[] = $this->connection->quoteIdentifier($field);
        };

        return $fields;
    }
}
