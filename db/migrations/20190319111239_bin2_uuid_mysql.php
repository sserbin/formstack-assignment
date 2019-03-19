<?php


use Phinx\Migration\AbstractMigration;

class Bin2UuidMysql extends AbstractMigration
{
    /**
     * compat for https://dev.mysql.com/doc/refman/8.0/en/miscellaneous-functions.html#function_bin-to-uuid
     */
    public function up(): void
    {
        $this->execute("
            CREATE
                FUNCTION BIN_TO_UUID(uuid BINARY(16))
                RETURNS VARCHAR(36)
                RETURN LOWER(CONCAT(
                SUBSTR(HEX(uuid), 1, 8), '-',
                SUBSTR(HEX(uuid), 9, 4), '-',
                SUBSTR(HEX(uuid), 13, 4), '-',
                SUBSTR(HEX(uuid), 17, 4), '-',
                SUBSTR(HEX(uuid), 21)
            ));
        ");
    }

    public function down(): void
    {
        $this->execute('drop function BIN_TO_UUID');
    }
}
