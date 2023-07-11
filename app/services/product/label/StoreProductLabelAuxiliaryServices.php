<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\services\product\label;


use app\dao\product\label\StoreProductLabelAuxiliaryDao;
use app\services\BaseServices;

/**
 * 标签辅助表
 * Class StoreProductLabelAuxiliaryServices
 * @package app\services\product\label
 */
class StoreProductLabelAuxiliaryServices extends BaseServices
{

    /**
     * StoreProductLabelAuxiliaryServices constructor.
     * @param StoreProductLabelAuxiliaryDao $dao
     */
    public function __construct(StoreProductLabelAuxiliaryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 关联标签
     * @param int $productId
     * @param array $labelIds
     * @return bool
     */
    public function saveLabelRelation(int $productId, array $labelIds)
    {
        $data = [];
        foreach ($labelIds as $labelId) {
            $data[] = [
                'product_id' => $productId,
                'label_id' => $labelId
            ];
        }
        $this->dao->delete(['product_id' => $productId]);
        if ($labelIds) {
            $this->dao->saveAll($data);
        }
        return true;
    }
}
