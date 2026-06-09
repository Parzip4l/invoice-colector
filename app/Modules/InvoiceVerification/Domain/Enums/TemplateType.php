<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum TemplateType: string
{
    case GENERATED_DOCUMENT = 'GENERATED_DOCUMENT';
    case FINAL_COMPILATION_ORDER = 'FINAL_COMPILATION_ORDER';
}
