<?php

class SqlResetLogger implements \Doctrine\DBAL\Logging\SQLLogger
{

    private $shouldReset = false;

    public function shouldReset()
    {
        return $this->shouldReset;
    }

    /**
     * Logs a SQL statement somewhere.
     *
     * @param string $sql The SQL to be executed.
     * @param array|null $params The SQL parameters.
     * @param array|null $types The SQL parameter types.
     *
     * @return void
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        if (stripos($sql, 'INSERT INTO') !== false || stripos($sql, 'UPDATE') !== false || stripos($sql, 'DELETE')) {
            $this->shouldReset = true;
        }
    }

    /**
     * Marks the last started query as stopped. This can be used for timing of queries.
     *
     * @return void
     */
    public function stopQuery()
    {
        // TODO: Implement stopQuery() method.
    }
}
