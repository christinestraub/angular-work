<?php
require_once 'basic_model.php';

define ('ARCHIVE_REMOVED', 'removed');
define ('ARCHIVE_CCC', 'CCC');
define ('ARCHIVE_NO_CCC', 'NO_CCC');

class ArchiveModel extends BasicModel {

    function __construct($db)
    {
        parent::__construct($db);
        $this->tableName = 'archives';
        $this->fields = array(
            'id',
            'file_name',
            'uuid',
            'user_id',
            'user_name',
            'uploaded_on',
            'project',
            'chassis',
            'project_id',
            'project_number',
            'status',
            'parse_flag',
            'region',
            'country'
        );
    }

    public function cccForCountries() {
        $rows = [];
        $sql = "SELECT COUNT(DISTINCT project_number) as count, `country` FROM `archives`
                GROUP BY `country` ORDER BY count DESC";

        $stmt = $this->conn->prepare($sql);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $total = 0;
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
                $total = $total + $row['count'];
            }

            foreach($rows as &$row) {
                $row['percent'] = number_format($row['count'] * 100 / $total, 0, '.', '');
            }
            return $rows;
        }

        return false;
    }

    public function cccForRegions() {
        $rows = [];
        $sql = "SELECT COUNT(DISTINCT project_number) as count, `region` FROM `archives`
                GROUP BY `region` ORDER BY count DESC";

        $stmt = $this->conn->prepare($sql);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $total = 0;
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
                $total = $total + $row['count'];
            }

            foreach($rows as &$row) {
                $row['percent'] = number_format($row['count'] * 100 / $total, 0, '.', '');
            }
            return $rows;
        }

        return false;
    }

    public function deleteOld($archive) {
        $sql = "DELETE FROM `archives`
                WHERE `project_number` = ? AND `project_id` = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $archive['project_number'], $archive['project_id']);
        return $stmt->execute();
    }

    /**
     * @param $project
     * @param $chassis
     * @param $projectId
     * @return array|bool
     */
    public function findProject($project, $chassis, $projectId) {
        $result = $this->db->archives()->where(array(
            "project" => $project,
            "chassis" => $chassis,
            "project_id" => $projectId,
        ))->limit(1, 0);

        if ($archive = $result->fetch()) {
            return $this->entity($archive);
        } else {
            return false;
        }
    }

    public function getAll() {
        $archives = array();

        $result = $this->db->archives()->where('status <> ?', ARCHIVE_REMOVED)->order('uploaded_on DESC');
        foreach ($result as $archive) {
            $archives[] = $this->entity($archive);
        }

        return $archives;
    }

    /**
     * @return array
     */
    public function getLatest() {
        $archives = array();
        $status = ARCHIVE_REMOVED;

        $sql = "SELECT t1.* FROM archives t1 WHERE t1.id = (SELECT t2.id
                 FROM archives t2
                 WHERE t2.project_number = t1.project_number
                  AND t2.project_id = t1.project_id
                  AND t2.chassis = t1.chassis
                 ORDER BY t2.uploaded_on DESC
                 LIMIT 1) AND status <> ? ORDER BY t1.uploaded_on DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $status);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $archives[] = $row;
            }
            return $archives;
        }

        return $archives;
    }

    public function getList($user_id) {
        $archives = array();

        $result = $this->db->archives()->where(
            'user_id', $user_id
        )->order('uploaded_on DESC');
        foreach ($result as $archive) {
            $archives[] = $this->entity($archive);
        }

        return $archives;
    }

    public function getListByUUID($user_uuid) {
        $archives = array();

        $result = $this->db->archives()->where('user_uuid', $user_uuid)->order('uploaded_on DESC');
        foreach ($result as $archive) {
            $archives[] = $this->entity($archive);
        }

        return $archives;
    }

    /**
     * @param $archive
     * @return array
     */
    public function getGroups($archive) {
        $archives = array();

        $result = $this->db->archives()->where([
            'project_number' => $archive['project_number'],
            'project_id' => $archive['project_id'],
            'chassis' => $archive['chassis'],
        ])->order('uploaded_on DESC');
        foreach ($result as $archive) {
            $archives[] = $this->entity($archive);
        }

        return $archives;
    }

    /**
     * @param $archive
     * @return array
     */
    public function getGroupCount($archive) {
        $result = $this->db->archives()->where([
            'project_number' => $archive['project_number'],
            'project_id' => $archive['project_id'],
            'chassis' => $archive['chassis'],
        ])->count();

        return $result;
    }

    /**
     * @param $month
     * @return array
     */
    public function getOld($month) {
        $archives = $this->getLatest();

        // check the last uploaded time
        foreach($archives as &$archive) {
            $archive['total'] = $this->getGroupCount($archive);
            if ($month > 0) {
                $archive['older'] = $this->getOlderCount($archive, $month);
            } else {
                $archive['older'] = 0;
            }
        }

        return $archives;
    }

    /**
     * @param $archive
     * @param $month
     * @return array
     */
    public function getOlder($archive, $month) {
        $archives = array();
        $date = new DateTime("now", new DateTimeZone("UTC"));
        $old = $date->sub(new DateInterval('P'.$month.'M'));

        $result = $this->db->archives()->where(
            "project_number = ? AND project_id = ? AND chassis = ? AND uploaded_on < ?",
            $archive['project_number'],
            $archive['project_id'],
            $archive['chassis'],
            $old->format('Y-m-d H:i:s')
        )->order('uploaded_on DESC');
        foreach ($result as $archive) {
            $archives[] = $this->entity($archive);
        }

        return $archives;
    }

    /**
     * @param $archive
     * @param $month
     * @return array
     */
    public function getOlderCount($archive, $month) {
        $date = new DateTime("now", new DateTimeZone("UTC"));
        $old = $date->sub(new DateInterval('P'.$month.'M'));

        $result = $this->db->archives()->where(
            "project_number = ? AND project_id = ? AND chassis = ? AND uploaded_on < ?",
            $archive['project_number'],
            $archive['project_id'],
            $archive['chassis'],
            $old->format('Y-m-d H:i:s')
        )->count();

        return $result;
    }

    /**
     * @param $archive
     * @param $month
     * @return array
     */
    public function getRecent($archive, $month) {
        $archives = array();
        $date = new DateTime("now", new DateTimeZone("UTC"));
        $old = $date->sub(new DateInterval('P'.$month.'M'));

        $result = $this->db->archives()->where(
            "project_number = ? AND project_id = ? AND chassis = ? AND uploaded_on >= ?",
            $archive['project_number'],
            $archive['project_id'],
            $archive['chassis'],
            $old->format('Y-m-d H:i:s')
        )->order('uploaded_on DESC');
        foreach ($result as $archive) {
            $archives[] = $this->entity($archive);
        }

        return $archives;
    }

    /**
     * @param $archive
     * @param $month
     * @return array
     */
    public function getRecentCount($archive, $month) {
        $date = new DateTime("now", new DateTimeZone("UTC"));
        $old = $date->sub(new DateInterval('P'.$month.'M'));

        $result = $this->db->archives()->where(
            "project_number = ? AND project_id = ? AND chassis = ? AND uploaded_on >= ?",
            $archive['project_number'],
            $archive['project_id'],
            $archive['chassis'],
            $old->format('Y-m-d H:i:s')
        )->count();

        return $result;
    }

    /**
     * @return array|bool
     */
    public function getRecycled() {
        $archives = array();

        $result = $this->db->archives()->where('status', ARCHIVE_REMOVED)
            ->order('uploaded_on DESC');
        foreach ($result as $archive) {
            $archives[] = $this->entity($archive);
        }

        return $archives;
    }

    public function previous($archive) {
        $archives = array();

        $project_number = $archive['project_number'];
        $project_id = $archive['project_id'];
        $chassis = $archive['chassis'];

        $result = $this->db->archives()->where([
            'project_number' => $project_number,
            'project_id' => $project_id,
            'chassis' => $chassis,
        ])
            ->order('uploaded_on DESC')->limit(2);
        foreach ($result as $archive) {
            $archives[] = $this->entity($archive);
        }

        if (sizeof($archives) > 1) {
            $archive = $archives[1];
        } else {
            $archive = $this->entity();
            $archive['file_name'] = 'Not exists';
        }

        return $archive;
    }

    /**
     * put an archive in recycled bin
     *
     * @param $uuid
     * @return array|bool
     */
    public function putRecycled($uuid) {
        $archive = $this->getByUUID($uuid);
        if (!$archive) return $archive;

        $archive['status'] = ARCHIVE_REMOVED;
        if (!$this->update($archive))
            return false;

        return $archive;
    }

    /**
     *
     * @param $id
     * @return array
     */
    public function remove($id) {
        $archive = $this->get($id);
        if (!$archive) {
            return $archive;
        }

        $archive['status'] = ARCHIVE_REMOVED;
        return $this->update($archive);
    }

    /**
     * @param $uuid
     * @return array|bool
     */
    public function restoreRecycled($uuid) {
        $archive = $this->getByUUID($uuid);
        if (!$archive) {
            return $archive;
        }

        // todo: if the ccc is exists then ARCHIVE_CCC, else ARCHIVE_NO_CCC
        $archive['status'] = ARCHIVE_CCC;
        return $this->update($archive);
    }

    /**
     * @return array|bool
     */
    public function uploadsForCountries() {
        $rows = [];
        $sql = "SELECT COUNT(*) as count, `region`, `country` FROM `archives` GROUP BY `country` ORDER BY `country`";

        $stmt = $this->conn->prepare($sql);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $total = 0;
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
                $total = $total + $row['count'];
            }

            foreach($rows as &$row) {
                $country = $row['country'];
                $result = $this->db->archives()->where('country', $country)-> order('uploaded_on DESC')->limit(1, 0);
                if ($result && $archive = $result->fetch())
                    $row['uploaded_on'] = $archive['uploaded_on'];
                else
                    $row['uploaded_on'] = '';

                $row['percent'] = number_format($row['count'] * 100 / $total, 0, '.', '');
            }
            return $rows;
        }

        return false;
    }

    /**
     * @return array|bool
     */
    public function uploadsForRegions() {
        $rows = [];
        $sql = "SELECT COUNT(*) as count, `region` FROM `archives` GROUP BY `region` ORDER BY `region`";

        $stmt = $this->conn->prepare($sql);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $total = 0;
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
                $total = $total + $row['count'];
            }

            foreach($rows as &$row) {
                $region = $row['region'];
                $result = $this->db->archives()->where('region', $region)-> order('uploaded_on DESC')->limit(1, 0);
                if ($result && $archive = $result->fetch())
                    $row['uploaded_on'] = $archive['uploaded_on'];
                else
                    $row['uploaded_on'] = '';

                $row['percent'] = number_format($row['count'] * 100 / $total, 0, '.', '');
            }
            return $rows;
        }

        return false;
    }

    public function uploadsForUsers() {
        $rows = [];
        $sql = "SELECT COUNT(*) as count, `user_id` FROM `archives` GROUP BY `user_id` ORDER BY `user_id`";

        $stmt = $this->conn->prepare($sql);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $total = 0;
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
                $total = $total + $row['count'];
            }

            foreach($rows as &$row) {
                $user_id = $row['user_id'];
                $result = $this->db->accounts()->where('id', $user_id);
                if ($result && $account = $result->fetch()) {
                    $row['user_id'] = $account['id'];
                    $row['username'] = $account['username'];
                } else {
                    $row['user_id'] = '';
                    $row['username'] = '';
                }

                $result = $this->db->archives()->where('user_id', $user_id)-> order('uploaded_on DESC')->limit(1, 0);
                if ($result && $archive = $result->fetch())
                    $row['uploaded_on'] = $archive['uploaded_on'];
                else
                    $row['uploaded_on'] = '';

                $row['percent'] = number_format($row['count'] * 100 / $total, 0, '.', '');
            }
            return $rows;
        }

        return false;
    }
}