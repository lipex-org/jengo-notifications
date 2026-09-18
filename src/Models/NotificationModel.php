<?php

declare(strict_types=1);

namespace Jengo\Notifications\Models;

use CodeIgniter\Model;
use Jengo\Notifications\Entities\DatabaseNotification;

class NotificationModel extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = DatabaseNotification::class;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
        'created_at',
        'updated_at',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function __construct(?\CodeIgniter\Database\ConnectionInterface $db = null, ?\CodeIgniter\Validation\ValidationInterface $validation = null)
    {
        parent::__construct($db, $validation);

        if (function_exists('config')) {
            $config = config('Notifications');
            if (!empty($config->table)) {
                $this->table = $config->table;
            }
        }
    }
}
