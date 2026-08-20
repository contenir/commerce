<?php

declare(strict_types=1);

namespace Contenir\Commerce\Model\Entity;

use Contenir\Db\Model\Entity\AbstractEntity;

/**
 * @property int $email_log_id
 * @property ?int $order_id
 * @property ?int $artist_enquiry_id
 * @property string $recipient
 * @property string $subject
 * @property ?string $message_class
 * @property string $status
 * @property ?string $error
 * @property ?string $created
 */
class EmailLogEntity extends AbstractEntity
{
    /** @var list<string> */
    protected array $primaryKeys = [
        'email_log_id',
    ];

    /** @var list<string> */
    protected array $columns = [
        'email_log_id',
        'order_id',
        'artist_enquiry_id',
        'recipient',
        'subject',
        'message_class',
        'status',
        'error',
        'created',
    ];
}
