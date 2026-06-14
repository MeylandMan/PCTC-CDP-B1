<?php

require_once ROOT_PATH . '/core/model.php';

class LocationModel extends Model
{
    protected string $table      = 'locations';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'site_name', 'city', 'country', 'latitude', 'longitude',
    ];

    /**
     * Retourne toutes les localisations sous forme de paires id => label.
     * Utilisé pour alimenter les <select> dans les formulaires d'appareils.
     */
    public function forSelect(): array
    {
        $rows = $this->findAll('site_name', 'ASC');
        $result = [];
        foreach ($rows as $row) {
            $result[$row['id']] = $row['site_name'] . ' — ' . $row['city'];
        }
        return $result;
    }
}