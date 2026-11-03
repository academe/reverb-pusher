<?php

namespace App\Support;

use App\Models\ReverbApp;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The one database read the Reverb server needs before it can accept
 * connections. It sits in its own container-resolved class so a test can
 * swap in a stand-in that fails the way an unreachable database does,
 * without pointing the suite at a real host that is down.
 */
class ActiveReverbApps
{
    /**
     * @return Collection<int, ReverbApp>
     */
    public function fetch(): Collection
    {
        return ReverbApp::active()->get();
    }

    /**
     * Drop the cached handle of the connection fetch() reads from, so the
     * next fetch() opens a new one.
     *
     * This uses disconnect() rather than purge() because the handle is the
     * only thing that can go stale. disconnect() drops it, and the same
     * connection object reconnects with the same settings on its next query.
     * purge() would also throw that object away, which gains nothing here.
     */
    public function disconnect(): void
    {
        DB::disconnect((new ReverbApp)->getConnectionName());
    }
}
