<?php

namespace App\Production\Erp;

use App\Models\Erp\Driver;
use App\Models\Erp\Vehicle;
use App\Models\Staff;
use App\Production\Support\ProductionException;
use Illuminate\Support\Facades\DB;

/** Техника и водители в ERP («Доработки ERP», п. 4; ТЗ П-ОБ-22…23б, ИС-ОБ-07…08а) */
class VehicleRegistry
{
    public const VEHICLE = 'vehicle';
    public const DRIVER = 'driver';

    /** Латинские буквы, похожие на кириллицу госномера: «А123ВС» и «A123BC» — один номер */
    private const PLATE_LOOKALIKES = ['A' => 'А', 'B' => 'В', 'E' => 'Е', 'K' => 'К', 'M' => 'М', 'H' => 'Н', 'O' => 'О', 'P' => 'Р', 'C' => 'С', 'T' => 'Т', 'Y' => 'У', 'X' => 'Х'];

    public function createVehicle(string $name, ?string $plate, bool $notUsed = false): Vehicle
    {
        $data = ['name' => $this->vehicleName($name), 'plate' => $this->plate($plate), 'not_used' => $notUsed];
        $this->assertPlateFree($data['plate']);
        $vehicle = Vehicle::create($data);
        ErpAudit::write(self::VEHICLE, $vehicle->id, 'created', null, $data);

        return $vehicle;
    }

    public function updateVehicle(Vehicle $vehicle, string $name, ?string $plate, bool $notUsed): void
    {
        $after = ['name' => $this->vehicleName($name), 'plate' => $this->plate($plate), 'not_used' => $notUsed];
        $before = ['name' => $vehicle->name, 'plate' => $vehicle->plate, 'not_used' => (bool) $vehicle->not_used];
        if ($after === $before) {
            return;
        }
        $this->assertPlateFree($after['plate'], $vehicle->id);
        $vehicle->update($after);
        ErpAudit::write(self::VEHICLE, $vehicle->id, 'updated', $before, $after);
    }

    /** Удалить из справочника (мягко): в новых заданиях не предлагается, прежние задания хранят свой снимок */
    public function deleteVehicle(Vehicle $vehicle): void
    {
        DB::transaction(function () use ($vehicle) {
            $drivers = $vehicle->drivers()->pluck('drivers.id')->all();
            $vehicle->drivers()->detach();
            $vehicle->delete();
            ErpAudit::write(self::VEHICLE, $vehicle->id, 'deleted', ['name' => $vehicle->name, 'plate' => $vehicle->plate, 'driver_ids' => $drivers], null);
        });
    }

    /** Закрепить водителя за машиной или открепить */
    public function setVehicleDriver(Vehicle $vehicle, Driver $driver, bool $assigned): void
    {
        $has = $vehicle->drivers()->whereKey($driver->id)->exists();
        if ($has === $assigned) {
            return;
        }
        if ($assigned) {
            $vehicle->drivers()->attach($driver->id);
        } else {
            $vehicle->drivers()->detach($driver->id);
        }
        ErpAudit::write(self::VEHICLE, $vehicle->id, $assigned ? 'driver_assigned' : 'driver_unassigned', null, ['driver' => ErpAudit::person((int) $driver->staff_id)]);
    }

    /** Внести сотрудника в список водителей */
    public function addDriver(int $staffId): Driver
    {
        if (!Staff::whereKey($staffId)->where('working', 1)->exists()) {
            throw ProductionException::invalid('Сотрудник не найден или не работает');
        }
        $driver = Driver::withTrashed()->where('staff_id', $staffId)->first();
        if ($driver && !$driver->trashed()) {
            throw ProductionException::invalid('Сотрудник уже в списке водителей');
        }
        if ($driver) {
            $driver->restore();
            $driver->update(['not_used' => false]);
            ErpAudit::write(self::DRIVER, $driver->id, 'restored', null, ['staff' => ErpAudit::person($staffId)]);

            return $driver;
        }
        $driver = Driver::create(['staff_id' => $staffId, 'not_used' => false]);
        ErpAudit::write(self::DRIVER, $driver->id, 'created', null, ['staff' => ErpAudit::person($staffId)]);

        return $driver;
    }

    public function setDriverNotUsed(Driver $driver, bool $notUsed): void
    {
        if ((bool) $driver->not_used === $notUsed) {
            return;
        }
        $driver->update(['not_used' => $notUsed]);
        ErpAudit::write(self::DRIVER, $driver->id, 'updated', ['not_used' => !$notUsed], ['not_used' => $notUsed]);
    }

    /** Убрать из списка водителей (мягко) и открепить от машин; прежние задания хранят водителя на момент назначения */
    public function deleteDriver(Driver $driver): void
    {
        DB::transaction(function () use ($driver) {
            $vehicles = $driver->vehicles()->pluck('vehicles.id')->all();
            $driver->vehicles()->detach();
            $driver->delete();
            ErpAudit::write(self::DRIVER, $driver->id, 'deleted', ['staff' => ErpAudit::person((int) $driver->staff_id), 'vehicle_ids' => $vehicles], null);
        });
    }

    /** Ключ сравнения госномеров: без пробелов и дефисов, латиница-двойник — кириллицей */
    public static function plateKey(?string $plate): ?string
    {
        if ($plate === null || $plate === '') {
            return null;
        }
        $key = preg_replace('/[\s\-]+/u', '', mb_strtoupper($plate));

        return strtr($key, self::PLATE_LOOKALIKES);
    }

    private function assertPlateFree(?string $plate, ?int $exceptId = null): void
    {
        $key = self::plateKey($plate);
        if ($key === null) {
            return;
        }
        $taken = Vehicle::whereNotNull('plate')->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))->get(['id', 'name', 'plate'])
            ->first(fn (Vehicle $v) => self::plateKey($v->plate) === $key);
        if ($taken) {
            throw ProductionException::invalid("Госномер {$plate} уже у машины «{$taken->name}»");
        }
    }

    private function vehicleName(string $name): string
    {
        $name = mb_substr(trim(preg_replace('/\s+/u', ' ', $name)), 0, 255);
        if ($name === '') {
            throw ProductionException::invalid('Укажите наименование (тип) машины');
        }

        return $name;
    }

    /** Госномер как ввели, но заглавными и без лишних пробелов */
    private function plate(?string $plate): ?string
    {
        $plate = trim(preg_replace('/\s+/u', ' ', (string) $plate));
        if ($plate === '') {
            return null;
        }
        if (mb_strlen($plate) > 20) {
            throw ProductionException::invalid('Госномер длиннее 20 знаков');
        }

        return mb_strtoupper($plate);
    }
}
