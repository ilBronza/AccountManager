<?php

namespace IlBronza\AccountManager\Tests\Support;

use Illuminate\Database\SQLiteConnection as BaseConnection;

/**
 * CRUD and FormField use MySQL schema queries when building forms.
 * Translate only those queries so HTTP tests can render forms on SQLite.
 * All application data queries still run against the real fixture database.
 */
class SqliteConnection extends BaseConnection
{
	public function select($query, $bindings = [], $useReadPdo = true)
	{
		if (preg_match('/^describe\s+`?([a-zA-Z0-9_]+)`?$/i', trim($query), $matches)) {
			return $this->describeTable($matches[1]);
		}

		if (preg_match('/^SHOW COLUMNS FROM `?([a-zA-Z0-9_]+)`? WHERE Field = [\'"]([a-zA-Z0-9_]+)[\'"]$/i', trim($query), $matches)) {
			return array_values(array_filter(
				$this->describeTable($matches[1]),
				static fn ($column) => $column->Field === $matches[2]
			));
		}

		return parent::select($query, $bindings, $useReadPdo);
	}

	private function describeTable(string $table): array
	{
		$columns = parent::select('PRAGMA table_info("' . $table . '")');
		if ($columns === []) {
			throw new \RuntimeException('Cannot describe missing fixture table: ' . $table);
		}

		return array_map(static function ($column) use ($table) {
			$type = strtolower($column->type);
			if ($type === 'varchar') {
				$type = 'varchar(255)';
			}
			if ($table === 'users__userdata' && $column->name === 'gender') {
				$type = "enum('m','f','nd')";
			}

			return (object) [
				'Field' => $column->name,
				'Type' => $type,
				'Null' => $column->notnull || $column->pk ? 'NO' : 'YES',
				'Key' => $column->pk ? 'PRI' : '',
				'Default' => $column->dflt_value,
				'Extra' => $column->pk && $type === 'integer' ? 'auto_increment' : '',
			];
		}, $columns);
	}
}
