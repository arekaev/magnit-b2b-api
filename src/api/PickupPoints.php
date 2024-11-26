<?php

namespace Magnit\Api;

class PickupPoints extends BaseClass
{

    /**
     * Получить список пунктов выдачи.
     *
     * @param array $filters Опциональные фильтры (например, город, регион).
     * @return array Список пунктов выдачи.
     * @throws \Magnit\Exceptions\ApiException
     */
    public function listPickupPoints(int $page = 1, int $size = 100): array
    {
        return $this->client->request('GET', '/v1/magnit-post/pickup-points', [
            'page' => $page,
            'size' => $size,
        ]);
    }

    /**
     * Получить информацию о конкретном пункте выдачи.
     *
     * @param string $pointId ID пункта выдачи.
     * @return array Информация о пункте.
     * @throws \Magnit\Exceptions\ApiException
     */
    public function getPickupPoint(string $pointId): array
    {
        return $this->client->request('GET', "/pickup-points/{$pointId}");
    }
}
