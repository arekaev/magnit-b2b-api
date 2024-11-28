<?php

namespace Magnit\Api\MagnitPost;

use Magnit\Api\BaseClass;

class PickupPoints extends BaseClass
{
    /**
     * Получить список пунктов выдачи.
     *
     * @param int $page Номер страницы, начинающийся с единицы (1..N)
     * @param int $size Размер возвращаемой страниц (1..1000)
     * @return array Список пунктов выдачи.
     */
    public function listPickupPoints(int $page = 1, int $size = 100): array
    {
        $size = $size > 1000 ? 1000 : $size;

        return $this->client->request('GET', sprintf('api/v1/magnit-post/pickup-points?page=%d&size=%d', abs($page), abs($size)));
    }
}
