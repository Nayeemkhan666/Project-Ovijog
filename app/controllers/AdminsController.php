<?php

/**
 * Admins controller that handle request's prefix with 'admins'.
 */
class AdminsController extends Controller
{

    private $adminModel;
    private $postModel;
    private $userModel;

    /**
     * Default constructor.
     */
    public function __construct()
    {
        security();
        if (!$_SESSION['is_admin']) {
            redirect('errors');
        }

        $this->adminModel = $this->model('Admin');
        $this->postModel = $this->model('Post');
        $this->userModel = $this->model('User');
    }

    /**
     * This method handle requests '/admins/index' and '/admins'.
     * 
     * @return void
     */
    public function index()
    {
        $totalUser = count($this->adminModel->getAllUsers());

        $totalPost = $this->postModel->totalPostCount();

        $totalSolved = $this->postModel->totalSolvedCount();

        $totalReport = count($this->adminModel->getAllReports());

        $data = [
            'total_user' => $totalUser,
            'total_post' => $totalPost,
            'total_solved' => $totalSolved,
            'total_unsolved' => ($totalPost - $totalSolved),
            'total_report' => $totalReport
        ];

        $this->view('/admins/index', $data);
    }

    /**
     * This method handle requests '/admins/managePost'.
     * 
     * @return void
     */
    public function managePost()
    {

        $data = [
            'posts' => $this->postModel->getAllPosts()
        ];

        $this->view('/admins/managePost', $data);
    }

    /**
     * This method handle requests '/admins/manageReport'.
     * 
     * @return void
     */
    public function manageReport()
    {

        $data = [
            'reports' => $this->adminModel->getAllReports(),
        ];

        $this->view('/admins/manageReport', $data);
    }

    /**
     * This method handle requests '/admins/manageCategory'.
     * 
     * @return void
     */
    public function manageCategory()
    {
        $categories = $this->postModel->getCategories();

        $data = [
            'categories' => $categories,
        ];

        $this->view('/admins/manageCategory', $data);
    }

    /**
     * This method handle requests '/admins/addCategory'.
     * 
     * @return void
     */
    public function addCategory()
    {
        $category = str_replace(['/', '<', '>', '"'], '', $_POST['category']);

        if ($this->adminModel->addCategory($category)) {
            flash('admin', 'Category Added');
        } else {
            flash('admin', 'Category Already Exist', 'alert alert-danger');
        }


        redirect('admins/manageCategory');
    }

    /**
     * This method handle requests '/admins/removeCategory/params'.
     * 
     * @param $category: category name
     * @return void
     */
    public function removeCategory($category)
    {
        $this->adminModel->removeCategory($category);

        flash('admin', 'Category Removed');
        redirect('admins/manageCategory');
    }

    /**
     * This method handle requests '/admins/manageUsers'.
     * 
     * @return void
     */
    public function manageUsers()
    {
        $data = [
            'users' => $this->adminModel->getAllUsers()
        ];

        $this->view('/admins/manageUsers', $data);
    }

    /**
     * This method handle requests '/admins/manageAdmin'.
     * 
     * @return void
     */
    public function manageAdmin()
    {

        $data = [
            'admins' => $this->adminModel->getAllAdmins(),
        ];

        $this->view('/admins/manageAdmin', $data);
    }

    /**
     * This method handle requests '/admins/makeAdmin'.
     * 
     * @return void
     */
    public function makeAdmin()
    {
        $id = htmlentities($_POST['id']);


        if (!$this->userModel->getUserById($id)) {
            flash('admin', 'No User Found With Given Id', 'alert alert-danger');
            redirect('admins/manageAdmin');
        } elseif ($this->adminModel->makeAdmin($id)) {
            flash('admin', 'Admin Added');
            redirect('admins/manageAdmin');
        } else {
            die('Something went wrong');
        }
    }

    /**
     * This method handle requests '/admins/removeAdminShip/params'.
     * 
     * @param $id: user_id
     * @return void
     */
    public function removeAdminShip($id)
    {
        if ($id == $_SESSION['user_id']) {
            flash('admin', 'You Cannot Remove You From Admins', 'alert alert-danger');
            redirect('admins/manageAdmin');
        } elseif ($this->adminModel->removeAdminShip($id)) {
            flash('admin', 'Admin removed');
            redirect('admins/manageAdmin');
        } else {
            die('Something went wrong');
        }
    }

    /**
     * This method handle requests '/admins/deletePost/params'.
     * 
     * @param $id: post_id
     * @return void
     */
    public function deletePost($id)
    {
        if ($this->postModel->deletePost($id)) {
            flash('admin', 'Post Removed');
            redirect('admins/managePost');
        } else {
            die('Something went wrong');
        }
    }


    /**
     * This method handle requests '/admins/deletePortedPost/params'.
     * 
     * @param $id: post_id
     * @return void
     */
    public function deleteReportedPost($id)
    {
        if ($this->postModel->deletePost($id)) {
            flash('admin', 'Post Removed');
            redirect('admins/manageReport');
        } else {
            die('Something went wrong');
        }
    }

    /**
     * This method handle requests '/admins/deleteUser/params'.
     * 
     * @param $id: user_id
     * @return void
     */
    public function deleteUser($id)
    {
        if ($id == $_SESSION['user_id']) {
            flash('admin', 'You Cannot Remove You', 'alert alert-danger');
            redirect('admins/manageUsers');
        } elseif ($this->adminModel->deleteUser($id)) {
            flash('admin', 'User Removed');
            redirect('admins/manageUsers');
        } else {
            die('Something went wrong');
        }
    }
}
