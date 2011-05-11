<?php
/*
 * This file is part of Sesshin library.
 *
 * (c) Przemek Sobstel <http://sobstel.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sesshin\Storage;

class Apc extends Storage {

	protected function doStore($key, $value, $ttl = null) {
		return apc_store($key, $value, $ttl);
	}

	protected function doFetch($key) {
    $value = apc_fetch($key, $success);
    if ($success) {
      return $value;
    }
    return false;
	}

	protected function doDelete($key) {
    return apc_delete($key);
	}

}
