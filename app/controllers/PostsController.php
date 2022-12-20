<?php

/**
 * Posts controller that handle request's prefix with 'posts'.
 */
class PostsController extends Controller
{

    private $postModel;
    private $userModel;
    private $commentModel;
    private $notificationModel;
    private $tagModel;

    /**
     * Default constructor.
     */
    public function __construct()
    {
        security();

        $this->postModel = $this->model('Post');
        $this->userModel = $this->model('User');
        $this->commentModel = $this->model('Comment');
        $this->tagModel = $this->model('Tag');
        $this->notificationModel = $this->model('Notification');
    }

    /**
     * This method handle requests '/posts/getAllTags'.
     * 
     * @return void
     */
    public function getAllTags()
    {

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = $this->tagModel->getAllTags();
            echo json_encode($data);
        }
    }

    /**
     * This method handle requests '/posts/load/params'.
     * 
     * @param $page: page number
     * @return void
     */
    public function load($page = 1)
    {
        sleep(1);

        $categories = $_POST['categories'];
        $key = $_POST['key'];

        $posts = $this->postModel->filterPostsWithLimit($page, $categories, $key);

        $upVotes = [];
        $downVotes = [];

        $upCount = [];
        $downCount = [];

        $viewCount = [];

        $tags = [];

        foreach ($posts as $post) {
            if ($this->postModel->isVoted($post->post_id)) {
                if ($this->postModel->getVote($post->post_id) == 1) {
                    $upVotes[$post->post_id] = 1;
                } else {
                    $downVotes[$post->post_id] = 1;
                }
            }

            $upCount[$post->post_id] = $this->postModel->getUpVotes($post->post_id);
            $downCount[$post->post_id] = $this->postModel->getdownVotes($post->post_id);
            $viewCount[$post->post_id] = $this->postModel->getViewCount($post->post_id);

            $tags[$post->post_id] = $this->tagModel->getTags($post->post_id);
        }

        $data = [
            'posts' => $posts,
            'up-votes' => $upVotes,
            'down-votes' => $downVotes,
            'up-count' => $upCount,
            'down-count' => $downCount,
            'view-count' => $viewCount,
            'tags' => $tags
        ];


        $this->view('posts/list', $data);
    }

    /**
     * This method handle requests '/posts/markSolved'.
     * 
     * @return void
     */
    public function markSolved()
    {
        if (isAcademicOfficial()) {
            return;
        }

        $post_id = htmlentities($_POST['post_id']);

        echo $this->postModel->markSolved($post_id);

        $commentedUsers = $this->postModel->commentedUsers($post_id);
        foreach ($commentedUsers as $commentedUser) {
            $this->addSolvedNotification($commentedUser->user_id, $post_id);
        }

        $votedUsers = $this->postModel->votedUsers($post_id);
        foreach ($votedUsers as $votedUser) {
            $this->addSolvedNotification($votedUser->user_id, $post_id);
        }
    }

    /**
     * This method handle requests '/posts/comment/params'.
     * 
     * @param $id: comment_id
     * @return void
     */
    public function comment($id)
    {

        $commentMsg = htmlentities($_POST['comment']);

        $comment_id = $this->commentModel->addComment($id, $commentMsg);

        $comment = $this->commentModel->getComment($comment_id);

        $data = [
            'comment' => $comment->comment,
            'user_id' => $_SESSION['user_id'],
            'comment_id' => $comment_id,
            'created_time' => $comment->created_time
        ];

        $this->addCommentNotification($id);

        echo json_encode($data);
    }

    /**
     * This method handle requests '/posts/vote/params0/params1'.
     * 
     * @param $params0: post_id
     * @param $params1: isagree
     * @return void
     */
    public function vote($params0, $params1)
    {
        if (isAcademicOfficial()) {
            return;
        }

        $this->postModel->vote($params0, $params1);

        $this->addVoteNotification($params0);

        $data = [
            'upCount' => $this->postModel->getUpVotes($params0),
            'downCount' => $this->postModel->getDownVotes($params0)
        ];

        echo json_encode($data);
    }

    /**
     * This method handle requests '/posts/index' and '/index'.
     * 
     * @return void
     */
    public function index()
    {

        $posts = $this->postModel->getAllPosts();

        $notifications = $this->notificationModel->getAllNotification();

        $upVotes = [];
        $downVotes = [];

        $upCount = [];
        $downCount = [];

        $viewCount = [];

        $tags = [];

        foreach ($posts as $post) {
            if ($this->postModel->isVoted($post->post_id)) {
                if ($this->postModel->getVote($post->post_id) == 1) {
                    $upVotes[$post->post_id] = 1;
                } else {
                    $downVotes[$post->post_id] = 1;
                }
            }

            $upCount[$post->post_id] = $this->postModel->getUpVotes($post->post_id);
            $downCount[$post->post_id] = $this->postModel->getdownVotes($post->post_id);
            $viewCount[$post->post_id] = $this->postModel->getViewCount($post->post_id);

            $tags[$post->post_id] = $this->tagModel->getTags($post->post_id);
        }


        $data = [
            'posts' => $posts,
            'up-votes' => $upVotes,
            'down-votes' => $downVotes,
            'up-count' => $upCount,
            'down-count' => $downCount,
            'view-count' => $viewCount,
            'tags' => $tags,
            'categories' => $this->postModel->getCategories(),
            'notifications' => $notifications
        ];

        $this->view('posts/index', $data);
    }

    /**
     * This method handle requests '/posts/report/params'.
     * 
     * @param $id: post_id
     * @return void
     */
    public function report($id)
    {
        if (isAcademicOfficial()) {
            redirect('posts');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $data = [
                'category' => htmlentities($_POST['reportCategory']),
                'feedback' => htmlentities($_POST['feedback']),
                'post_id' => $id
            ];

            $this->postModel->report($data);

            unset($_POST);

            $this->view('posts/report', $data);
        }
    }

    /**
     * This method handle requests '/posts/show/params'.
     * 
     * @param $id: post_id
     * @return void
     */
    public function show($id)
    {

        $this->postModel->addView($id);

        $post = $this->postModel->getPostById($id);

        $upVoted = 0;
        $downVoted = 0;

        if ($this->postModel->isVoted($post->post_id)) {
            if ($this->postModel->getVote($post->post_id) == 1) {
                $upVoted = 1;
            } else {
                $downVoted = 1;
            }
        }

        $data = [
            'post' => $post,
            'comments' => $this->commentModel->getPostComment($id),
            'up-voted' => $upVoted,
            'down-voted' => $downVoted,
            'up-count' => $this->postModel->getUpVotes($id),
            'down-count' => $this->postModel->getdownVotes($id),
            'view-count' => $this->postModel->getViewCount($post->post_id),
            'tags' => $this->tagModel->getTags($post->post_id)
        ];

        $this->view('posts/show', $data);
    }

    /**
     * This method handle requests '/posts/add'.
     * 
     * @return void
     */
    public function add()
    {
        if (isAcademicOfficial()) {
            redirect('posts');
        }

        $categories = $this->postModel->getCategories();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);


            $data = [
                'categories' => $categories,

                'title' => trim($_POST['title']),
                'category' => trim($_POST['category']),
                'body' => trim($_POST['body']),
                'image' => $_FILES['image'],
                'tags' => trim($_POST['tags']),
                'user_id' => $_SESSION['user_id'],
                'title_err' => '',
                'body_err' => ''
            ];

            if (!empty($data['image']['name'])) {
                $prod = uniqid();

                $filename = $data["image"]['name'];
                $filename = explode(".", $filename);
                $extension = end($filename);
                $newfilename = $prod . "." . $extension;

                move_uploaded_file($data['image']['tmp_name'], dirname(APPROOT) . "\public\dir\\" . $newfilename);

                $url = URLROOT . '/dir/' . $newfilename;
                $data['image'] = $url;
            } else {
                $data['image'] = "";
            }

            if (empty($data['title'])) {
                $data['title_err'] = 'Please enter title';
            }
            if (empty($data['body'])) {
                $data['body_err'] = 'Please enter body text';
            }

            if (empty($data['title_err']) && empty($data['body_err'])) {
                $data['tags'] = explode(',', $data['tags']);
                if ($this->postModel->addPost($data)) {
                    flash('post_message', 'Post Added');
                    redirect('posts');
                } else {
                    die('Something went wrong');
                }
            }

            $this->view('posts/add', $data);
        } else {

            $data = [
                'categories' => $categories,

                'title' => '',
                'category' => '',
                'body' => '',
                'tags' => '',
                'image' => ''
            ];

            $this->view('posts/add', $data);
        }
    }

    /**
     * This method handle requests '/posts/edit/params'.
     * 
     * @param $id:post_id
     * @return void
     */
    public function edit($id)
    {

        $post = $this->postModel->getPostById($id);

        if ($post->user_id != $_SESSION['user_id']) {
            redirect('posts');
        }

        $categories = $this->postModel->getCategories();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'categories' => $categories,

                'post_id' => $id,
                'title' => trim($_POST['title']),
                'category' => trim($_POST['category']),
                'body' => trim($_POST['body']),
                'image' => $_FILES['image'],
                'user_id' => $_SESSION['user_id'],
                'tags' => trim($_POST['tags']),
                'title_err' => '',
                'body_err' => ''
            ];

            if (!empty($data['image']['name'])) {
                $prod = uniqid();

                $filename = $data["image"]['name'];
                $filename = explode(".", $filename);
                $extension = end($filename);
                $newfilename = $prod . "." . $extension;

                move_uploaded_file($data['image']['tmp_name'], dirname(APPROOT) . "\public\dir\\" . $newfilename);

                $url = URLROOT . '/dir/' . $newfilename;
                $data['image'] = $url;
            } else {
                $data['image'] = "";
            }

            if (empty($data['title'])) {
                $data['title_err'] = 'Please enter title';
            }
            if (empty($data['body'])) {
                $data['body_err'] = 'Please enter body text';
            }

            if (empty($data['title_err']) && empty($data['body_err'])) {
                if ($this->postModel->updatePost($data)) {
                    flash('post_message', 'Post Update');
                    redirect('posts');
                } else {
                    die('Something went wrong');
                }
            }

            $this->view('posts/edit', $data);
        } else {

            $post = $this->postModel->getPostById($id);

            if ($post->user_id != $_SESSION['user_id']) {
                redirect('posts');
            }

            $tags = $this->tagModel->getTags($id);
            $tagText = '';
            foreach ($tags as $tag) {
                $tagText .= $tag->tag . ', ';
            }

            $data = [
                'categories' => $categories,

                'post_id' => $id,
                'title' => $post->title,
                'category' => $post->category,
                'body' => $post->body,
                'tags' => $tagText,
                'img_link' => $post->img_link,
                'title_err' => '',
                'body_err' => ''
            ];

            $this->view('posts/edit', $data);
        }
    }

    /**
     * This method handle requests '/posts/delete/params'.
     * 
     * @param $id:post_id
     * @return void
     */
    public function delete($id)
    {
        $post = $this->postModel->getPostById($id);

        if ($post->user_id != $_SESSION['user_id']) {
            redirect('posts');
        }

        if ($this->postModel->deletePost($id)) {
            flash('post_message', 'Post Removed');
            redirect('posts');
        } else {
            die('Something went wrong');
        }
    }

    /**
     * This method handle requests '/posts/addVoteNotification/params'.
     * 
     * @param $post_id:post_id
     * @return void
     */
    public function addVoteNotification($post_id)
    {
        $post = $this->postModel->getPostById($post_id);
        $user_id = $post->user_id;
        $text = '<p>You have a new vote on your post <a href="' . URLROOT . '/posts/show/' . $post_id . '">' . $post->title . '</a><p>';

        $this->notificationModel->addNotification($user_id, $post_id, $text);
    }

    /**
     * This method handle requests '/posts/addCommentNotification/params'.
     * 
     * @param $post_id:post_id
     * @return void
     */
    public function addCommentNotification($post_id)
    {
        $post = $this->postModel->getPostById($post_id);
        $user_id = $post->user_id;
        $text = '<p>You have a new comment on your post <a href="' . URLROOT . '/posts/show/' . $post_id . '">' . $post->title . '</a><p>';

        $this->notificationModel->addNotification($user_id, $post_id, $text);
    }

    /**
     * This method handle requests '/posts/addSolvedNotification/params0/params1'.
     * 
     * @param $user_id:user_id
     * @param $post_id:post_id
     * @return void
     */
    public function addSolvedNotification($user_id, $post_id)
    {
        $post = $this->postModel->getPostById($post_id);
        //$user_id=$post->user_id;
        $text = '<p>Post <a href="' . URLROOT . '/posts/show/' . $post_id . '">' . $post->title . '</a> is now solved!<p>';

        $this->notificationModel->addNotification($user_id, $post_id, $text);
    }

    /**
     * Helper method to calculates similarity between 2 text.
     *
     * @param $text1 : text 1
     * @param $text2 : text 2
     * @return float|int similarity
     */
    public function validateSimilarTexts($text1, $text2)
    {

        $similarity = new CosineSimilarity();
        $tokenizer = new WhitespaceTokenizer();

        $text1 = preg_replace('/[^a-z0-9]+/i', ' ', $text1);
        $text2 = preg_replace('/[^a-z0-9]+/i', ' ', $text2);

        $setA = $tokenizer->tokenize($text1);
        $setB = $tokenizer->tokenize($text2);


        return $similarity->similarity($setA, $setB);
    }

    /**
     * This method handle requests '/posts/getSimilarPosts'.
     *
     * @return void
     */
    public function getSimilarPosts()
    {
        $content = $_POST['content'];

        $posts = $this->postModel->getAllPosts();
        $similarPosts = [];

        foreach ($posts as $post) {
            $postContent = $post->title . ' ' . $post->category . ' ' . $post->body;
            $similarity = $this->validateSimilarTexts($content, $postContent);
            if ($similarity > 0.50) {
                $similarPosts[$post->post_id] = $similarity;
            }
        }

        arsort($similarPosts);

        $suggestedPosts = [];

        foreach ($similarPosts as $k => $v) {
            $suggestedPosts[] = $this->postModel->getPostById($k);
            if (count($suggestedPosts) == 3) break;
        }

        $data = [];

        $data['suggestedPosts'] = $suggestedPosts;

        echo json_encode($data);
    }
}
