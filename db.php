<?php

class DB
{
    private $db;
    private $select = array();
    private $action = 'SELECT';
    private $table;
    private $offset = 0;
    private $limit;
    private $order_by = array();
    private $set = array();
    private $where = array();
    private $or_where = array();
    private $where_in = array();
    private $like = array();
    private $group_by = array();
    public $log = array();
    public $query;

    public function __construct($host, $database, $user, $pass)
    {
        $this->db = new mysqli($host, $user, $pass, $database);
        if ($this->db->connect_errno) {
            echo $this->db->connect_errno;
        }
        $this->db->set_charset('utf8mb4');
        return $this->db;
    }

    public function clear()
    {
        $this->select = array();
        $this->action = 'SELECT';
        $this->table = false;
        $this->offset = 0;
        $this->limit = false;
        $this->query = false;
        $this->order_by = array();
        $this->set = array();
        $this->where = array();
        $this->or_where = array();
        $this->where_in = array();
        $this->like = array();
        $this->group_by = array();
    }

    public function fields($field)
    {
        if (is_array($field)) {
            $this->select = array_merge($this->select, $field);
        } else {
            $this->select[] = $field;
        }
        return $this;
    }

    public function set($field, $value = null)
    {
        if (is_array($field)) {
            $this->set = array_merge($this->set, $field);
        } else {
            $this->set[$field] = $value;
        }
        return $this;
    }

    public function where($field, $value = null)
    {
        if ($field) {
            if (is_array($field)) {
                $this->where = array_merge($this->where, $field);
            } else {
                $this->where[$field] = $value;
            }
        }
        return $this;
    }

    public function or_where($field, $value = null)
    {
        if ($field) {
            $this->or_where[][$field] = $value;
        }
        return $this;
    }

    public function where_in($field, $value = null)
    {
        if (is_array($field)) {
            $this->where_in = array_merge($this->where_in, $field);
        } else {
            $this->where_in[$field] = $value;
        }
        return $this;
    }

    public function like($field, $value, $type = 'both')
    {
        $start = '%';
        $end = '%';
        if ($type == 'before') {
            $start = '%';
            $end = '';
        } else if ($type == 'after') {
            $start = '';
            $end = '%';
        }
        $this->like[$field] = $start . $this->clean($value) . $end;
        return $this;
    }

    public function group_by($field)
    {
        if (is_array($field)) {
            $this->group_by = array_merge($this->group_by, $field);
        } else {
            $this->group_by[] = $field;
        }
        return $this;
    }

    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    public function update()
    {
        $this->action = 'UPDATE';
        $this->generate();
        $this->result = $this->execute($this->query);
        $this->clear();
        return $this->result;
    }

    public function delete()
    {
        $this->action = 'DELETE';
        $this->generate();
        $this->result = $this->execute($this->query);
        $this->clear();
        return $this->result;
    }

    public function insert()
    {
        $this->action = 'INSERT';
        $this->generate();
        $this->result = $this->execute($this->query);
        $this->clear();
        return $this->result;
    }

    public function select_row()
    {
        if ($this->query) {
            $this->result = $this->execute($query);
        } else {
            $this->generate();
            $this->result = $this->execute($this->query);
        }
        $item = $this->result->fetch_object();
        $this->clear();
        return $item;
    }

    public function select()
    {
        if ($this->query) {
            $this->result = $this->execute($this->query);
        } else {
            $this->generate();
            $this->result = $this->execute($this->query);
        }
        $items = array();
        while ($item = $this->result->fetch_object()) {
            $items[] = $item;
        }
        $this->clear();
        return $items;
    }

    public function select_plain()
    {
        if ($this->query) {
            $this->result = $this->execute($this->query);
        } else {
            $this->generate();
            $this->result = $this->execute($this->query);
        }

        $items = array();
        while ($item = $this->result->fetch_object()) {
            $items[] = $item->{$this->select[0]};
        }
        $this->clear();
        return $items;
    }

    public function select_var()
    {
        if ($this->query) {
            $this->result = $this->execute($query);
        } else {
            $this->generate();
            $this->result = $this->execute($this->query);
        }
        $item = $this->result->fetch_object();
        $item = $item->{$this->select[0]};
        $this->clear();
        return $item;
    }

    public function count()
    {
        if ($this->query) {
            $this->result = $this->execute($this->query);
        } else {
            $this->generate();
            $this->result = $this->execute($this->query);
        }

        $total = $this->result->num_rows;
        $this->clear();
        return $total;
    }

    public function query($query)
    {
        $this->query = $query;
        return $this;
    }

    public function execute($query)
    {

        $this->log[] = $query;

        $this->query = false;
        $result = $this->db->query($query);
        if (!$result) {
            echo 'Error: ' . $this->db->error . '<br>';
            echo 'Query: ' . $query;
            exit();
        }
        return $result;
    }

    public function insert_id()
    {
        return $this->db->insert_id;
    }

    public function limit($offset, $limit = false)
    {
        if (!$limit) {
            $this->limit = $offset;
        } else {
            $this->offset = $offset;
            $this->limit = $limit;
        }
        return $this;
    }

    public function order_by($key, $sort = false)
    {
        if (strstr($key, ',')) {
            $items = explode(',', $key);
            foreach ($items as $item) {
                list($key, $sort) = explode(' ', trim($item));
                $this->order_by[$key] = $sort;
            }
        } else {
            $this->order_by[$key] = $sort;
        }
        return $this;
    }

    public function clean($value)
    {
        if ($value === null) {
            $value = null;
        } else if (!is_null($value)) {
            $value = $this->db->real_escape_string($value);
        }
        return $value;
    }

    public function generate()
    {
        if ($this->action == 'UPDATE') {
            $query = 'UPDATE ';
            $query .= $this->table;
            $query .= ' SET ';
            $i = 0;
            foreach ($this->set as $key => $value) {
                $key = $this->clean($key);
                $value = $this->clean($value);
                if ($i) {
                    $query .= ',';
                }
                $query .= $key . '=';
                if (is_null($value)) {
                    $query .= 'NULL';
                } else {
                    $query .= "'" . $value . "'";
                }
                $i++;
            }

        } else if ($this->action == 'INSERT') {
            $query = 'INSERT INTO ';
            $query .= $this->table;
            $query .= ' (';
            $i = 0;
            foreach ($this->set as $key => $value) {
                $key = $this->clean($key);
                if ($i) {
                    $query .= ',';
                }
                $query .= $key;
                $i++;
            }
            $query .= ' ) VALUES (';
            $i = 0;
            foreach ($this->set as $key => $value) {
                $value = $this->clean($value);
                if ($i) {
                    $query .= ',';
                }
                if (is_null($value)) {
                    $query .= 'NULL';
                } else {
                    $query .= "'" . $value . "'";
                }
                $i++;
            }
            $query .= ')';

        } else if ($this->action == 'DELETE') {
            $query = 'DELETE FROM ';
            $query .= $this->table;

        } else if ($this->action == 'SELECT') {
            if (!$this->select) {
                $this->select = array('*');
            }

            $query = 'SELECT ' . implode(', ', $this->select);
            $query .= ' FROM ' . $this->table;
        }

        $symbols = '/(\<|\>|\<\=|\>\=|\!\=|\=)/';

        if ($this->where) {
            $query .= ' WHERE ';
            $i = 0;
            foreach ($this->where as $key => $value) {
                $key = $this->clean($key);

                if ($i) {
                    $query .= ' AND ';
                }

                if ($value === null) {

                    if (preg_match($symbols, $key)) {
                        $query .= $key;
                    } else if (strstr($key, ' IS ')) {
                        $query .= $key;
                    } else {
                        $query .= $key . ' IS NULL';
                    }

                } else {
                    $value = $this->clean($value);

                    if (preg_match($symbols, $key)) {
                        $key .= '';
                    } else {
                        $key .= '=';
                    }

                    $query .= $key . ' ';

                    if (is_numeric($value)) {
                        $query .= $value;
                    } else {
                        $query .= "'" . $value . "'";
                    }
                }

                $i++;
            }
        }

        if ($this->or_where) {
            if (strstr($query, ' WHERE ')) {
                $i = 1;
            } else {
                $i = 0;
                $query .= ' WHERE ';
            }
            foreach ($this->or_where as $or_where) {
                foreach ($or_where as $key => $value) {
                    $key = $this->clean($key);
                    $value = $this->clean($value);
                    if ($i) {
                        $query .= ' OR ';
                    }

                    if ($value === null) {

                        if (preg_match($symbols, $key)) {
                            $query .= $key;
                        } else {
                            $query .= $key . ' IS NULL';
                        }

                    } else {
                        $value = $this->clean($value);

                        if (preg_match($symbols, $key)) {
                            $key .= '';
                        } else {
                            $key .= '=';
                        }

                        $query .= $key . ' ';

                        if (is_numeric($value)) {
                            $query .= $value;
                        } else {
                            $query .= "'" . $value . "'";
                        }

                    }

                    $i++;
                }
            }
        }

        if ($this->where_in) {
            if (strstr($query, ' WHERE ')) {
                $i = 1;
            } else {
                $i = 0;
                $query .= ' WHERE ';
            }

            foreach ($this->where_in as $key => $value) {
                $key = $this->clean($key);
                if ($i) {
                    $query .= ' AND ';
                }
                $query .= $key . ' IN (';
                $j = 0;
                foreach ($value as $val) {
                    $val = $this->clean($val);
                    if ($j) {
                        $query .= ',';
                    }
                    $query .= "'" . $val . "'";
                    $j++;
                }
                $query .= ')';
                $i++;
            }
        }

        if ($this->like) {
            if (strstr($query, ' WHERE ')) {
                $i = 1;
            } else {
                $i = 0;
                $query .= ' WHERE ';
            }
            foreach ($this->like as $key => $value) {
                $key = $this->clean($key);
                if ($i) {
                    $query .= ' AND ';
                }
                $query .= $key . " LIKE '" . $value . "'";
                $i++;
            }
        }

        if ($this->group_by) {
            $query .= ' GROUP BY ' . implode(', ', $this->group_by);
        }

        if ($this->order_by) {
            $query .= ' ORDER BY ';
            $i = 0;
            foreach ($this->order_by as $key => $value) {
                if ($i) {
                    $query .= ', ';
                }
                $query .= $key . ' ' . $value;
                $i++;
            }
        }

        if ($this->offset && $this->limit) {
            $query .= ' LIMIT ' . (int) $this->offset . ',' . (int) $this->limit;
        } else if ($this->limit) {
            $query .= ' LIMIT ' . (int) $this->limit;
        }

        $this->query = $query;
    }
}
