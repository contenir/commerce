<?php

declare(strict_types=1);

namespace Contenir\Commerce\Model\Entity;

use Contenir\Commerce\Model\Repository\ArtistEnquiryFileRepository;
use Contenir\Db\Model\Entity\AbstractEntity;

/**
 * @property int $artist_enquiry_id
 * @property string $name
 * @property string $email
 * @property ?string $telephone
 * @property ?string $website
 * @property ?string $instagram
 * @property ?string $bio
 * @property ?string $statement
 * @property ?string $medium
 * @property ?string $preferred_timing
 * @property ?string $how_heard
 * @property string $status
 * @property ?string $staff_notes
 * @property ?string $created
 * @property ?string $updated
 * @property array<int, ArtistEnquiryFileEntity> $files
 */
class ArtistEnquiryEntity extends AbstractEntity
{
    /** @var list<string> */
    protected array $primaryKeys = [
        'artist_enquiry_id',
    ];

    /** @var list<string> */
    protected array $columns = [
        'artist_enquiry_id',
        'name',
        'email',
        'telephone',
        'website',
        'instagram',
        'bio',
        'statement',
        'medium',
        'preferred_timing',
        'how_heard',
        'status',
        'staff_notes',
        'created',
        'updated',
    ];

    /** @var array<string, array<string, mixed>> */
    protected array $relations = [
        'files' => [
            'type'   => AbstractEntity::RELATION_MANY,
            'column' => ['artist_enquiry_id'],
            'table'  => [
                'class'  => ArtistEnquiryFileRepository::class,
                'column' => ['artist_enquiry_id'],
            ],
        ],
    ];
}
