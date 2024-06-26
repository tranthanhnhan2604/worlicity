<?php
require_once 'config/db.php';
class UserModel
{
    protected $db;
    public function __construct()
    {
        $connect = new Connect();
        $this->db = $connect->getConnection();
    }

    //Lấy DS tất cả User
    public function getUsers()
    {
        $query = "SELECT * FROM users";
        $result = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    // Đếm tổng số người dùng trong bảng users
    public function countUsers()
    {
        $query = "SELECT COUNT(user_id) AS total_count FROM users";
        $result = $this->db->query($query)->fetch(PDO::FETCH_ASSOC);

        if ($result && isset($result['total_count'])) {
            return $result['total_count'];
        }

        return 0;
    }

    public function getUserByID($user_id)
    {
        $query = "SELECT u.*, at.account_type_name
        FROM users u
        LEFT JOIN account_types at ON u.account_type_id = at.account_type_id
        WHERE u.user_id = :user_id";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':user_id', $user_id);
        $statement->execute();
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user;
    }

    // lấy role
    public function getAccountTypes()
    {
        $query = "SELECT * FROM account_types";
        $result = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    // Hàm đếm tổng số bất động sản trong bảng properties
    public function countProperties()
    {
        $query = "SELECT COUNT(property_id) AS total_count FROM properties";
        $result = $this->db->query($query)->fetch(PDO::FETCH_ASSOC);

        if ($result && isset($result['total_count'])) {
            return $result['total_count'];
        }

        return 0;
    }

    //Đếm số lượng view trong bảng property
    public function countTotalViewCount()
    {
        $query = "SELECT SUM(view_count) AS total_view_count FROM (
                SELECT view_count FROM properties
                UNION ALL
                SELECT view_count FROM news_blog
            ) AS view_counts";
        $statement = $this->db->prepare($query);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if ($result && isset($result['total_view_count'])) {
            return $result['total_view_count'];
        }

        return 0;
    }

    // Đếm số lượng ảnh trong bảng property_images
    public function countPropertyImages()
    {
        $query = "SELECT COUNT(image_id) AS total_count FROM property_images";
        $result = $this->db->query($query)->fetch(PDO::FETCH_ASSOC);

        if ($result && isset($result['total_count'])) {
            return $result['total_count'];
        }

        return 0;
    }

    // Đếm tổng số tin tức trong bảng news_blog
    public function countNewsBlogs()
    {
        $query = "SELECT COUNT(news_id) AS total_count FROM news_blog";
        $result = $this->db->query($query)->fetch(PDO::FETCH_ASSOC);

        if ($result && isset($result['total_count'])) {
            return $result['total_count'];
        }

        return 0;
    }

    public function countTotalByColumn($columnName, $tableName, $user_id)
    {
        $query = "SELECT COUNT($columnName) AS total_count FROM $tableName WHERE user_id = :user_id";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':user_id', $user_id);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if ($result && isset($result['total_count'])) {
            return $result['total_count'];
        }

        return 0;
    }

    public function getPropertyByUser($user_id)
    {
        $query = "SELECT p.*, pi.image_url, u.fullname, u.avatar_url
        FROM properties p
        LEFT JOIN (
            SELECT property_id, MIN(image_url) AS image_url
            FROM property_images
            GROUP BY property_id
        ) pi ON p.property_id = pi.property_id
        LEFT JOIN users u ON p.user_id = u.user_id
        WHERE p.user_id = :user_id
        ORDER BY p.property_id DESC";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':user_id', $user_id);
        $statement->execute();
        $properties = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $properties;
    }

    public function getUsersWithPropertiesAdmin()
    {
        $query = "SELECT u.*, p.property_id, p.property_name, p.description, i.image_url,
                    u.phone_number, u.account_type_id, at.account_type_name, u.created_at,
                    u.updated_at, u.fullname, u.user_address, u.state, u.about, u.avatar_url,
                    u.status
                    FROM users u
                    LEFT JOIN properties p ON u.user_id = p.user_id
                    LEFT JOIN (
                        SELECT property_id, MIN(image_url) AS image_url
                        FROM property_images
                        GROUP BY property_id
                    ) i ON p.property_id = i.property_id
                    LEFT JOIN account_types at ON u.account_type_id = at.account_type_id";
        $result = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);

        $users = [];
        foreach ($result as $row) {
            $user_id = $row['user_id'];
            if (!isset($users[$user_id])) {
                $users[$user_id] = [
                    'user_id' => $row['user_id'],
                    'username' => $row['username'],
                    'phone_number' => $row['phone_number'],
                    'account_type_id' => $row['account_type_id'],
                    'account_type_name' => $row['account_type_name'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                    'fullname' => $row['fullname'],
                    'user_address' => $row['user_address'],
                    'state' => $row['state'],
                    'about' => $row['about'],
                    'avatar_url' => $row['avatar_url'],
                    'status' => $row['status'],
                    'properties' => [],
                ];
            }

            if (!empty($row['property_id'])) {
                $users[$user_id]['properties'][] = [
                    'property_id' => $row['property_id'],
                    'property_name' => $row['property_name'],
                    'description' => $row['description'],
                    'image_url' => $row['image_url'],
                ];
            }
        }

        return $users;
    }

    public function getAllPropertyAdmin()
    {
        $query = "SELECT p.*, pi.image_url, u.fullname, u.avatar_url, p.status AS property_status
        FROM properties p
        LEFT JOIN (
            SELECT property_id, MIN(image_url) AS image_url
            FROM property_images
            GROUP BY property_id
        ) pi ON p.property_id = pi.property_id
        LEFT JOIN users u ON p.user_id = u.user_id
        ORDER BY p.property_id DESC";

        $result = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }
}
