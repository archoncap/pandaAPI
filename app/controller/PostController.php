<?php
/**
 * 文章控制器
 * 
 * 处理文章相关的所有请求
 */
namespace App\Controller;

use App\Model\PostModel;
use PandaAPI\Cache\Cache;

class PostController extends BaseController
{
    /**
     * 文章列表
     * GET /api/v1/posts
     */
    public function index(): array
    {
        [$page, $perPage] = $this->getPageParams();

        $model = new PostModel();
        $result = $model->getList($perPage, $page);

        return $this->paginate(
            $result['current_page'],
            $result['per_page'],
            $result['total'],
            $result['data']
        );
    }

    /**
     * 文章详情
     * GET /api/v1/posts/{id}
     */
    public function show(int $id): array
    {
        $model = new PostModel();
        $post = $model->getDetail($id);

        if (!$post) {
            return $this->error('文章不存在', 404);
        }

        // 增加浏览量
        $model->incrementViews($id);

        return $this->success($post);
    }

    /**
     * 创建文章
     * POST /api/v1/posts
     */
    public function store(): array
    {
        $data = $this->input();

        if (empty($data['title']) || empty($data['content']) || empty($data['user_id'])) {
            return $this->error('标题、内容和用户ID不能为空', 400);
        }

        $data['status']     = 1;
        $data['views']      = 0;
        $data['created_at'] = format_date();

        $model = new PostModel();
        $id = $model->insert($data);

        return $this->success(['id' => $id], '创建成功');
    }

    /**
     * 更新文章
     * PUT /api/v1/posts/{id}
     */
    public function update(int $id): array
    {
        $data = $this->input();

        $model = new PostModel();
        $post = $model->find($id);

        if (!$post) {
            return $this->error('文章不存在', 404);
        }

        if (!empty($data)) {
            $model->update($data, ['id' => $id]);
        }

        return $this->success($model->getDetail($id), '更新成功');
    }

    /**
     * 删除文章
     * DELETE /api/v1/posts/{id}
     */
    public function destroy(int $id): array
    {
        $model = new PostModel();
        $post = $model->find($id);

        if (!$post) {
            return $this->error('文章不存在', 404);
        }

        $model->delete(['id' => $id]);

        return $this->success(null, '删除成功');
    }

    /**
     * 热门文章
     * GET /api/v1/posts/hot
     */
    public function hot(): array
    {
        $limit = (int)$this->input('limit', 10);
        $model = new PostModel();
        return $this->success($model->getHot($limit));
    }

    /**
     * 文章统计
     * GET /api/v1/posts/stats
     */
    public function stats(): array
    {
        $model = new PostModel();
        return $this->success($model->getStats());
    }
}
