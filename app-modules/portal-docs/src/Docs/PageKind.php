<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\Docs;

enum PageKind: string
{
    case Mdx = 'mdx';
    case Endpoint = 'endpoint';
}
