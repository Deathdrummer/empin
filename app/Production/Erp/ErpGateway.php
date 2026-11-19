<?php

namespace App\Production\Erp;

use Carbon\CarbonInterface;

/**
 * Шлюз ERP — единственная точка, через которую модуль производства (задания, выходы, табель, отчёты, API приложения)
 * получает данные ERP и отдаёт ей готовность объекта. Состав — по ТЗ, раздел 10: 10.1 «Из ERP в приложение»,
 * 10.2 «Из приложения в ERP». Связи — только по постоянным идентификаторам ERP (п. 10.3.1).
 *
 * Сейчас ERP и модуль живут в одном Laravel и одной базе — реализация {@see DatabaseErpGateway} читает таблицы ERP.
 * Если ERP вынесут в отдельную систему или перепишут, меняется только реализация этого интерфейса (например, на
 * клиент API ERP); модуль производства и приложение не меняются. Вне папки `app/Production/Erp` модуль не обращается
 * к таблицам и моделям ERP.
 *
 * Все методы — пакетные (по списку id), чтобы при выносе ERP не получить по запросу на каждую строку.
 * Отсутствующие в ERP записи в ответе просто нет: удаление в ERP не должно ломать историю (п. 10.3.2).
 */
interface ErpGateway
{
    // ---- Сотрудники (10.1): постоянный идентификатор, ФИО, должность, связь с учётной записью ----

    /**
     * @param int[] $staffIds
     * @return array<int, array{id: int, name: string, patronymic: ?string, position: ?string, working: bool}> по id
     */
    public function people(array $staffIds): array;

    /** @return int[] работающие сотрудники (не уволенные) */
    public function workingStaffIds(): array;

    /** Есть рабочая учётная запись в приложении (П-ОБ-21) */
    public function hasAccount(int $staffId): bool;

    /** @return array<int, array{id: int, name: string, patronymic: ?string, position: ?string, working: bool}> все с учётной записью, по ФИО */
    public function accountPeople(): array;

    // ---- Права пользователей (10.1): выданные права с настройками ----

    /**
     * Права приложения, выданные сотруднику в ERP (лично и через роль), с настройками каждой выдачи
     *
     * @return array<int, array{permission: string, settings: array<int, array{depth_days: ?int, scope_all: bool, staff_ids: int[], brigade_ids: int[], all_brigades: bool, date_from: ?string, date_to: ?string}>}>
     */
    public function grants(int $staffId): array;

    // ---- Бригады (10.1): состав, бригадир, история вступления и выхода ----

    /**
     * @param int[] $brigadeIds
     * @return array<int, array{id: int, name: string, foreman_staff_id: ?int, disbanded_on: ?string}> по id
     */
    public function brigades(array $brigadeIds): array;

    /**
     * Бригады, действующие после даты (не расформированы или расформированы позже), по названию
     *
     * @return array<int, array{id: int, name: string, foreman_staff_id: ?int, disbanded_on: ?string}>
     */
    public function brigadesWorkingOn(CarbonInterface $date): array;

    /**
     * Состав бригад на дату — действующие члены и бригадир (П-ОБ-16)
     *
     * @param int[] $brigadeIds
     * @return array<int, int[]> brigade_id => staff_id[]
     */
    public function brigadeStaffOn(array $brigadeIds, CarbonInterface $date): array;

    /**
     * Члены бригад на дату в порядке вступления (без бригадира, если его нет строкой состава)
     *
     * @param int[] $brigadeIds
     * @return array<int, int[]> brigade_id => staff_id[]
     */
    public function brigadeMembersOn(array $brigadeIds, CarbonInterface $date): array;

    /**
     * Бригады сотрудника на дату: член по составу или бригадир; расформированные к дате не действуют
     *
     * @return array<int, array{id: int, name: string, is_foreman: bool, joined_on: ?string}> по id, по названию
     */
    public function brigadesOfStaff(int $staffId, CarbonInterface $date): array;

    /**
     * История членства (для табеля: доступ с месяца вступления до месяца выхода)
     *
     * @param int[]|null $brigadeIds null — без отбора по бригадам
     * @return array<int, array{brigade_id: int, staff_id: int, joined_on: string, left_on: ?string}>
     */
    public function memberships(?array $brigadeIds, ?int $staffId = null): array;

    // ---- Транспорт и водители (10.1) ----

    /**
     * @param int[]|null $vehicleIds null — все используемые
     * @return array<int, array{id: int, name: string, plate: ?string, not_used: bool, driver_ids: int[]}> по id
     */
    public function vehicles(?array $vehicleIds = null): array;

    /**
     * @param int[]|null $driverIds null — все используемые, с работающим сотрудником
     * @return array<int, array{id: int, staff_id: int, not_used: bool, working: bool}> по id
     */
    public function drivers(?array $driverIds = null): array;

    /** @return array{id: int, staff_id: int, not_used: bool, working: bool}|null */
    public function driverOfStaff(int $staffId): ?array;

    /** @return int[] сотрудники, внесённые в список водителей (в том числе «не используется») */
    public function driverStaffIds(): array;

    // ---- Объекты (10.1): номер, название, расположение, категория, ответственный, закреплённые бригады ----

    /**
     * @param int[] $objectIds
     * @return array<int, array{id: int, number: ?string, title: ?string, titul: ?string, locality: ?string, active: bool, responsible_staff_id: ?int, readiness: ?float}> по id
     */
    public function objects(array $objectIds): array;

    /**
     * Активные объекты по поиску в номере, названии, титуле, расположении: точное совпадение номера — первым
     *
     * @return int[]
     */
    public function searchObjects(string $search, int $limit): array;

    /**
     * Активные объекты, где сотрудник ответственный, по номеру ERP
     *
     * @param int[] $exceptIds
     * @return int[]
     */
    public function objectsOfResponsible(int $staffId, array $exceptIds, bool $incompleteOnly, int $limit): array;

    /**
     * Активные объекты, за которыми закреплена бригада, по номеру ERP
     *
     * @return int[]
     */
    public function objectsOfBrigade(int $brigadeId, ?string $search, int $limit): array;

    /**
     * Из списка — только активные (и, если нужно, с готовностью меньше 100 %), порядок сохраняется
     *
     * @param int[] $objectIds
     * @return int[]
     */
    public function activeObjectIds(array $objectIds, bool $incompleteOnly): array;

    // ---- Справочник видов работ и план объекта (10.1) ----

    /**
     * @param int[]|null $workTypeIds null — весь действующий справочник; по id — и удалённые (для истории)
     * @return array<int, array{id: int, name: string, unit: ?string, deleted: bool}> по id
     */
    public function workTypes(?array $workTypeIds = null): array;

    /**
     * Актуальная редакция плана объекта; null — плана нет (П-ОБ-31)
     *
     * @return array{revision_id: int, items: array<int, float>}|null items: work_type_id => плановое количество
     */
    public function currentPlan(int $objectId): ?array;

    // ---- Готовность объекта (10.2): приложение — источник, ERP хранит значение (п. 10.3.7) ----

    /** Значение, которое хранит ERP; $lock — заблокировать запись до конца транзакции */
    public function storedReadiness(int $objectId, bool $lock = false): ?float;

    /** Передать в ERP актуальную готовность объекта; null — готовность не задана (возврат к «не задана») */
    public function storeReadiness(int $objectId, ?float $percent): void;
}
