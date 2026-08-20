<?php

declare(strict_types=1);

namespace Contenir\Commerce\Model\Entity;

use Contenir\Db\Model\Entity\AbstractEntity;

/**
 * @property int $artist_enquiry_file_id
 * @property int $artist_enquiry_id
 * @property string $filename
 * @property string $path
 * @property ?string $mime_type
 * @property ?int $size
 * @property ?string $created
 */
class ArtistEnquiryFileEntity extends AbstractEntity
{
    /** @var list<string> */
    protected array $primaryKeys = [
        'artist_enquiry_file_id',
    ];

    /** @var list<string> */
    protected array $columns = [
        'artist_enquiry_file_id',
        'artist_enquiry_id',
        'filename',
        'path',
        'mime_type',
        'size',
        'created',
    ];
}
