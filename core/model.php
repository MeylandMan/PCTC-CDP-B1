<?php

require_once __DIR__ . '/../config/database.php';

abstract class Model
{
    // ---------------------------------------------------------------------------
    // Propriétés à surcharger dans chaque model enfant
    // ---------------------------------------------------------------------------

    /** Nom de la table MySQL associée à ce model */
    protected string $table;

    /** Nom de la clé primaire (presque toujours 'id') */
    protected string $primaryKey = 'id';

    /**
     * Colonnes autorisées à l'écriture (INSERT / UPDATE).
     * Si vide, toutes les colonnes sont autorisées — à restreindre
     * dans les models enfants pour éviter la mass-assignment.
     *
     * Exemple : ['firstname', 'lastname', 'email', 'role_id']
     */
    protected array $fillable = [];

    // ---------------------------------------------------------------------------
    // Connexion PDO — injectée depuis le Singleton
    // ---------------------------------------------------------------------------
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ===========================================================================
    // LECTURE
    // ===========================================================================

    /**
     * Récupère tous les enregistrements de la table.
     * Possibilité de trier : findAll('created_at', 'DESC')
     */
    public function findAll(string $orderBy = '', string $direction = 'ASC'): array
    {
        $sql = "SELECT * FROM {$this->table}";

        if ($orderBy !== '') {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql      .= " ORDER BY {$orderBy} {$direction}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findById(int|string $id): array|false
    {
        $sql  = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    public function findOneBy(array $conditions): array|false
    {
        [$whereClause, $bindings] = $this->buildWhere($conditions);

        $sql  = "SELECT * FROM {$this->table} WHERE {$whereClause} LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->fetch();
    }

    public function findBy(
        array  $conditions,
        string $orderBy   = '',
        string $direction = 'ASC',
        ?int   $limit     = null
    ): array {
        [$whereClause, $bindings] = $this->buildWhere($conditions);

        $sql = "SELECT * FROM {$this->table} WHERE {$whereClause}";

        if ($orderBy !== '') {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql      .= " ORDER BY {$orderBy} {$direction}";
        }

        if ($limit !== null) {
            $sql .= " LIMIT " . (int) $limit;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->fetchAll();
    }

    public function paginate(
        int    $page       = 1,
        int    $perPage    = 15,
        array  $conditions = [],
        string $orderBy    = '',
        string $direction  = 'ASC'
    ): array {
        $page    = max(1, $page);
        $offset  = ($page - 1) * $perPage;

        $whereClause = '';
        $bindings    = [];

        if (!empty($conditions)) {
            [$whereClause, $bindings] = $this->buildWhere($conditions);
            $whereClause = "WHERE {$whereClause}";
        }

        // Compte total
        $countSql = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($bindings);
        $total = (int) $countStmt->fetchColumn();

        // Données paginées
        $sql = "SELECT * FROM {$this->table} {$whereClause}";

        if ($orderBy !== '') {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql      .= " ORDER BY {$orderBy} {$direction}";
        }

        $sql .= " LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);

        return [
            'data'        => $stmt->fetchAll(),
            'total'       => $total,
            'perPage'     => $perPage,
            'currentPage' => $page,
            'lastPage'    => (int) ceil($total / $perPage),
        ];
    }

    public function count(array $conditions = []): int
    {
        $whereClause = '';
        $bindings    = [];

        if (!empty($conditions)) {
            [$whereClause, $bindings] = $this->buildWhere($conditions);
            $whereClause = "WHERE {$whereClause}";
        }

        $sql  = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);

        return (int) $stmt->fetchColumn();
    }

    // ===========================================================================
    // ÉCRITURE
    // ===========================================================================

    public function insert(array $data): int
    {
        $data = $this->filterFillable($data);

        if (empty($data)) {
            throw new InvalidArgumentException('Aucune donnée valide à insérer.');
        }

        $columns     = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($col) => ":{$col}", array_keys($data)));

        $sql  = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->prefixKeys($data));

        return (int) $this->db->lastInsertId();
    }

    public function update(int|string $id, array $data): int
    {
        $data = $this->filterFillable($data);

        if (empty($data)) {
            throw new InvalidArgumentException('Aucune donnée valide à mettre à jour.');
        }

        $setParts = array_map(fn($col) => "{$col} = :{$col}", array_keys($data));
        $setClause = implode(', ', $setParts);

        $sql  = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :__pk";
        $stmt = $this->db->prepare($sql);

        $bindings         = $this->prefixKeys($data);
        $bindings[':__pk'] = $id;

        $stmt->execute($bindings);

        return $stmt->rowCount();
    }

    public function delete(int|string $id): int
    {
        $sql  = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount();
    }

    // ===========================================================================
    // REQUÊTES PERSONNALISÉES
    // ===========================================================================

    public function query(string $sql, array $bindings = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->fetchAll();
    }

    public function queryOne(string $sql, array $bindings = []): array|false
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->fetch();
    }

    public function execute(string $sql, array $bindings = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->rowCount();
    }

    // ===========================================================================
    // MÉTHODES INTERNES
    // ===========================================================================

    private function buildWhere(array $conditions): array
    {
        $parts    = [];
        $bindings = [];

        foreach ($conditions as $column => $value) {
            if ($value === null) {
                $parts[] = "{$column} IS NULL";
            } else {
                $parts[]              = "{$column} = :{$column}";
                $bindings[":{$column}"] = $value;
            }
        }

        return [implode(' AND ', $parts), $bindings];
    }

    private function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_filter(
            $data,
            fn($key) => in_array($key, $this->fillable, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function prefixKeys(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[":{$key}"] = $value;
        }
        return $result;
    }
}