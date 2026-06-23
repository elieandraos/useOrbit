<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $role
 * @property string $status
 */
class OrganizationMember extends Pivot {}
