<?php
final class VinylsModel {

    private $db = null;

    private $notification_model = null;

    public function __construct() {
        $this->db = Database::getInstance();
        require_once MODELS . 'NotificationModel.php';
        $this->notification_model = new NotificationModel();
    }

    private function notificateVinylsQuantity() {
        $vinyls = $this->getVinylsOptimized(['stock' => 0]);
        foreach ($vinyls as $vinyl) {
            $this->notification_model->broadcastFor(
                Database::getInstance()->executeResults("SELECT id_user FROM users WHERE su = 1"),
                "Vinyl " . $vinyl['title'] . " out of stock!",
                "/vinyl?id=" . $vinyl['id_vinyl']
            );
        }
    }

    private function notificateVinylQuantity($id_vinyl) {
        $vinyl = $this->getVinyl($id_vinyl);
        if ($vinyl['stock'] <= 0) {
            $this->notification_model->broadcastFor(
                Database::getInstance()->executeResults("SELECT id_user FROM users WHERE su = 1"),
                "Vinyl " . $vinyl['title'] . " is out of stock!",
                "/vinyl?id=" . $vinyl['id_vinyl']
            );
        }
    }

    private function broadcastCartVinyl($id_vinyl) {
        $this->notification_model->broadcastFor(
            Database::getInstance()->executeResults(
                "SELECT id_user FROM carts WHERE id_vinyl = ?",
                'i',
                $id_vinyl
            ),
            "A vinyl in your cart has been updated.",
            "/vinyl?id=" . $id_vinyl
        );
    }

    private function broadcastVinyl($id_vinyl) {
        $this->notification_model->broadcast(
            "A new Vinyl landed here!",
            "/vinyl?id=$id_vinyl"
        );
    }

    private function applyFilters($query, $filters = []) {
        $filtersMap = [
            "id_vinyl" => ["query" => fn($value) => " AND v.id_vinyl = ? ", "type" => 'i'],
            "stock" => ["query" => fn($value) => " AND v.stock = ? ", "type" => 'i'],
            "id_album" => ["query" => fn($value) => " AND a.id_album = ? ", "type" => 'i'],
            "title" => ["query" => fn($value) => " AND a.title LIKE ?", "type" => 's', "value" => fn($value) => "%$value%"],
            "genre" => ["query" => fn($value) => " AND a.genre LIKE ?", "type" => 's', "value" => fn($value) => "%$value%"],
            "id_artist" => ["query" => fn($value) => " AND ar.id_artist = ? ", "type" => 'i'],
            "artist_name" => ["query" => fn($value) => " AND ar.name LIKE ?", "type" => 's', "value" => fn($value) => "%$value%"],
            "id_track" => ["query" => fn($value) => " AND ta.id_track = ? ", "type" => 'i'],
            "track_title" => ["query" => fn($value) => " AND t.title LIKE ?", "type" => 's', "value" => fn($value) => "%$value%"]
        ];

        $types = 'i';
        $values = [1];
        foreach ($filters as $key => $value) {
            $query .= $filtersMap[$key]["query"]($value);
            $types .= $filtersMap[$key]["type"];
            $values[] = isset($filtersMap[$key]["value"]) ? $filtersMap[$key]["value"]($value) : $value;
        }
        return $this->db->executeResults($query. ' GROUP BY a.id_album;', $types, ...$values);
    }

    /**
     * Gets a specified number of vinyls from the database.
     * @param n the number of vinyls to send
     * @param params a map of values to query the database on:
     * params structure:
     *  { 
     *      id -> ..., album -> ..., genre -> ...,
     *      artist -> ..., track -> ...
     *  }
     * @return json of vinyls data
     */    
    public function getVinyls($n, $params) {
        $query = "SELECT
            v.id_vinyl,
            v.stock,
            v.cost,
            a.title,
            a.cover,
            a.genre,
            ar.name AS artist
            FROM
            vinyls v JOIN albums a ON v.id_album = a.id_album
            JOIN artists ar ON a.id_artist = ar.id_artist";
        $keys = array_keys($params);
        // get the first key and switch on it to get the right string added to the query
        switch (reset($keys)) {
            case "id":
                $query = $query . " WHERE v.id_vinyl = " . $params["id"];
                break;
            case "album":
                $query = $query . " WHERE a.title LIKE '%" . $params["album"] . "%'";
                break;
            case "genre":
                $query = $query . " WHERE a.genre LIKE '%" . $params["genre"] . "%'";
                break;
            case "track":
                $query = $query . " JOIN albumstracks ta
                    ON a.id_album = ta.id_album JOIN tracks t
                    ON t.id_track = ta.id_track WHERE t.title LIKE '%". $params["track"] . "%'";
                    break;
            case "artist":
                $query = $query . " WHERE ar.name LIKE '%" . $params["artist"] . "%'";
                break;
            default;
        }
        // in case it needs a limitation
        if ($n > 0) {
            $query = $query . " LIMIT ?";
            $result = $this->db->executeResults($query, 'i', $n);
        } else {
            $result = $this->db->executeResults($query);
        }
        return $result;
    }

    public function getAllVinyls() {
        $vinyls = "SELECT
            v.id_vinyl,
            v.stock,
            a.title,
            v.type,
            v.inch,
            v.stock,
            v.rpm,
            v.cost
            FROM vinyls v , albums a
            WHERE v.id_album=a.id_album";
        return $this->db->executeResults($vinyls);
    }

    /**
     * Gets the full of a single vinyl (vinyl page)
     * from a given id.
     * @param id of the vinyl in question
     * @return array containing details on the vinyl
     */
    public function getVinylDetails($id) {
        // query to get vinyls info
        $vinyl = "SELECT 
            v.id_vinyl,
            v.cost,
            v.rpm,
            v.inch,
            v.type,
            v.stock,
            a.id_album,
            a.title,
            a.release_date,
            a.cover,
            a.genre,
            ar.name AS artist
            FROM 
            vinyls v
            JOIN albums a ON v.id_vinyl = a.id_album
            JOIN artists ar ON ar.id_artist = a.id_artist
            WHERE v.id_vinyl = ?";
        // query to get the tracks from a vinyl [needs id_album from previous query]
        $tracks = "SELECT
            t.title,
            t.duration
            FROM
            albums a
            JOIN albumstracks ta ON ta.id_album = a.id_album
            JOIN tracks t ON t.id_track = ta.id_track
            WHERE a.id_album = ?";
        // prepare statement
        $result = $this->db->executeResults($vinyl, "i", $id);
        if(!empty($result)) {
            $result["details"] = $result[0];
            // store id_album for the next query
            $album =  $result["details"]["id_album"];
            // prepare second statement
            $result["tracks"] = $this->db->executeResults($tracks, "i", $album);
        }
        return $result;
    }

    /**
     * get the vinyl details from the database (without tracks)
     * 
     * @param int $id_vinyl the id of the vinyl to get the details of
     * 
     * @return array containing the details of the vinyl
     */
    public function getVinyl($id_vinyl) {
        $vinyl = Database::getInstance()->executeResults(
            "SELECT 
                v.id_vinyl,
                v.stock,
                v.cost,
                v.rpm,
                v.inch,
                v.type,
                a.title,
                a.genre,
                a.cover,
                ar.name AS artist_name
                FROM vinyls v 
                JOIN albums a ON v.id_vinyl = a.id_album
                JOIN artists ar ON a.id_artist = ar.id_artist
                WHERE id_vinyl = ?",
            'i',
            $id_vinyl
        );
        return !empty($vinyl) ? $vinyl[0] : $vinyl;
    }


    /**
     * Function to be called to get the preview of a vinyl
     * (cart, checkout and order pages).
     * @param int $id of the vinyl to get the preview of
     * @return array containing the information on the vinyl
     */
    public function getCarousel() {
        // query to get vinyls info
        $query = "SELECT
            v.id_vinyl,
            a.title,
            a.cover,
            ar.name AS artist
            FROM 
            vinyls v
            JOIN albums a ON v.id_vinyl = a.id_album
            JOIN artists ar ON ar.id_artist = a.id_artist";
        // execute query
        $result['vinyls'] = $this->db->executeResults($query);
        return $result;
    }

    /**
     * Gets the order previews (user page)
     * from a given vinyl id.
     * @param id of the vinyl to get the preview of
     * @return json with the  preview infos
     */
    public function getUserOrderPreview($id) {
        $preview = [];
        $query = "SELECT
            v.cost,
            a.title,
            a.cover,
            FROM 
            vinyls v
            JOIN albums a ON v.id_vinyl = a.id_album
            WHERE v.id_vinyl = ?";
        // execute query
        $result = $this->db->executeResults($query, 'i', $id);
        if (!empty($result)):
            // store results
            $preview["cost"] = $result["cost"];
            $preview["title"] = $result["title"];
            $preview["cover"] = $result["cover"];
        endif;
        return $preview;
    }

    public function getSuggested($id) {
        $selected_vinyl = $this->getVinyl($id);
        return Database::getInstance()->executeResults(
            "SELECT 
                v.id_vinyl,
                v.stock,
                v.cost,
                v.rpm,
                v.inch,
                v.type,
                a.title,
                a.genre,
                a.cover,
                ar.name AS artist_name
                FROM vinyls v
                JOIN albums a ON v.id_album = a.id_album
                JOIN artists ar ON ar.id_artist = a.id_artist
                WHERE (a.genre = ? OR ar.name = ?)
                AND v.id_vinyl <> ?
                LIMIT 6", 
            "ssi", 
            $selected_vinyl["genre"], 
            $selected_vinyl["artist_name"],
            $id
        );
    }

    
    /**
     * Get the vinyls.
     *
     * @param array $filter the qury filter of the artist params to get the albums
     * filters can be:
     * - id_album
     * - title
     * - genre
     * - id_artist
     * - artist_name
     * - id_track
     * - track_title
     * 
     * @return array containing the albums 
     */
    public function getVinylsOptimized($filters) {
        Database::getInstance()->setHandler(Database::defaultHandler());
        return $this->applyFilters(
            "SELECT
                    GROUP_CONCAT(DISTINCT v.id_vinyl ORDER BY v.id_vinyl ASC SEPARATOR ', ') AS id_vinyl, 
                    GROUP_CONCAT(DISTINCT v.cost ORDER BY v.cost ASC SEPARATOR ', ') AS vinyl_cost, 
                    GROUP_CONCAT(DISTINCT v.stock ORDER BY v.stock ASC SEPARATOR ', ') AS vinyl_stock, 
                    a.id_album, 
                    a.title, 
                    a.release_date, 
                    a.genre, 
                    a.cover, 
                    ar.id_artist, 
                    ar.name AS artist_name, 
                    GROUP_CONCAT(DISTINCT ta.id_track ORDER BY ta.id_track ASC SEPARATOR ', ') AS track_ids,
                    GROUP_CONCAT(DISTINCT t.title ORDER BY t.title ASC SEPARATOR ', ') AS track_titles
                FROM vinyls v
                JOIN albums a ON v.id_album = a.id_album 
                JOIN artists ar ON a.id_artist = ar.id_artist
                JOIN albumstracks ta ON a.id_album = ta.id_album
                JOIN tracks t ON t.id_track = ta.id_track
                WHERE 1 = ?", 
            $filters
        );
    }

    /**
     * Get the albums of an artist.
     * @param array $filter the qury filter of the artist params to get the albums
     * filters can be:
     * - id_album
     * - title
     * - genre
     * - id_artist
     * - artist_name
     * - id_track
     * - track_title
     * 
     * @return array containing the albums 
     */
    public function getAlbums($filters) {
        return $this->applyFilters(
            "SELECT 
                    a.id_album,
                    a.title,
                    a.release_date,
                    a.genre,
                    a.cover,
                    ar.id_artist,
                    ar.name AS artist_name,
                    GROUP_CONCAT(DISTINCT ta.id_track ORDER BY ta.id_track ASC SEPARATOR ', ') AS track_ids,
                    GROUP_CONCAT(DISTINCT t.title ORDER BY t.title ASC SEPARATOR ', ') AS track_titles
                FROM albums a 
                JOIN artists ar ON a.id_artist = ar.id_artist
                JOIN albumstracks ta ON a.id_album = ta.id_album
                JOIN tracks t ON t.id_track = ta.id_track
                WHERE 1 = ?", 
            $filters
        );
    }

    public function addTrack($id_album, $title, $duration) {
        return Database::getInstance()->executeQueryAffectRows(
            "INSERT INTO `tracks` (title, duration) VALUES (?, ?)",
            'ss',
            $title, $duration
        ) && Database::getInstance()->executeQueryAffectRows(
            "INSERT INTO `albumstracks` (id_album, id_track) VALUES (?, ?)",
            'ii',
            $id_album, Database::getInstance()->getLastId()
        );
    }


    /**
     * Check if the artist exists in the database.
     * @param int $artist_id of the artist to check
     * 
     * @return bool true if the artist exists, false otherwise
     */
    private function checkArtist($artist_id) {
        return !empty($this->db->executeResults(
            "SELECT * FROM artists WHERE id_artist = ?",
            'i',
            $artist_id
        ));
    }


    /**
     * Create an artist in the database.
     * @param string $name of the artist
     * 
     * @return bool true if the artist was created, false otherwise
     */ 
    public function createArtist($name) {
        return $this->db->executeQueryAffectRows(
            "INSERT INTO artists (name) VALUES (?)",
            's',
            $name
        );
    }


    /**
     * Check if the album exists in the database.
     * @param int $album_id of the album to check
     * 
     * @return bool true if the album exists, false otherwise
     */
    private function checkAlbum($album_id) {
        return !empty($this->db->executeResults(
            "SELECT * FROM albums WHERE id_album = ?",
            'i',
            $album_id
        ));
    }


    /**
     * Create an album in the database.
     * @param string $title of the album
     * @param string $release_date of the album
     * @param string $genre of the album
     * @param array $artist of the album
     * 
     * @return bool true if the album was created, false otherwise
     */
    public function createAlbum($title, $release_date, $genre, $artist, $tracks) {
        // check if the artist already exists if not add it to the database

        if (!isset($artist['id_artist'])) {
            $artist['id_artist'] = '';
        }

        if (empty($artist['id_artist']) || !$this->checkArtist($artist['id_artist'])) {
            $artist = $this->createArtist($artist["name"]);
            if (!$artist) {
                return false;
            }
            $artist = Database::getInstance()->getLastId();
        } else {
            $artist = $artist['id_artist'];
        }

        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['cover']['tmp_name'];
            $name = $_FILES['cover']['name']; 
            $destinationDir = '/var/www/html/resources/img/albums/';
            $destination = $destinationDir . $name;
        
            if (!is_dir($destinationDir)) {
                mkdir($destinationDir, 0775, true);
            }

            if (!move_uploaded_file($tmpPath, $destination)) {
                return false;
            }
        } else {
            return false;
        }

        
        $result = $this->db->executeQueryAffectRows(
            "INSERT INTO albums (title, release_date, genre, cover, id_artist)
                VALUES (?, ?, ?, ?, ?)",
            'ssssi',
            $title, $release_date, $genre, $name, $artist
        );
        
        if ($result) {
            $last_id = Database::getInstance()->getLastId();    
            foreach ($tracks as $track) {
                $this->addTrack($last_id, $track['title'], $track['duration']);
            }
        }
        return $result ? $last_id : false;
    }

    /**
     * Add or update a vinyl to the database.
     * 
     * @param int $id_vinyl of the vinyl to update (null if adding)
     * @param array $album of the vinyl
     * @param array $artist of the vinyl's album
     * @param float $cost of the vinyl
     * @param int $stock of the vinyl
     * 
     * @return bool true if the vinyl was added, false otherwise
     */
    public function addVinyl($cost, $rpm, $inch, $type, $stock, $album, $id_vinyl = null) {
        // check if the album already exists if not add it to the database
        if($album ) {
            if(!isset($album['id_album']) && empty($album['id_album'])) {
                $album = $this->createAlbum($album["title"], $album["release_date"], $album["genre"], $album["artist"], $album['tracks']);
                if(!$album) {
                    return false;
                }
            } else {
                if(!$this->checkAlbum($album["id_album"])) {
                    return false;
                }
            }
        }
        
        $album = isset($album["id_album"]) ? $album['album_id'] : $album;
        if ($id_vinyl) {
            return $this->updateVinyl($id_vinyl, $cost, $rpm, $inch, $type, $stock, $album);
        }
        
        $result = $this->db->executeQueryAffectRows(
            "INSERT INTO vinyls (`cost`, `rpm`, `inch`, `type`, `stock`, `id_album`)
                VALUES (?, ?, ?, ?, ?, ?)",
            'diisii',
            $cost, $rpm, $inch, $type, $stock, $album
        );

        if ($result) {
            $this->broadcastVinyl(Database::getInstance()->getLastId());
        }
        return $result;
    }


    /**
     * Update a vinyl in the database.
     * @param int $id_vinyl of the vinyl to update
     * @param float $cost of the vinyl
     * @param int $rpm of the vinyl
     * @param int $inch of the vinyl
     * @param string $type of the vinyl
     * @param int $stock of the vinyl
     * 
     * @return bool true if the vinyl was updated, false otherwise
     */
    public function updateVinyl($id_vinyl, $cost = null, $rpm = null, $inch = null, $type = null, $stock = null, $id_album = null) {
        $fields = [
            'cost' => ['type' => 'd', 'value' => $cost],
            'rpm' => ['type' => 'i', 'value' => $rpm],
            'inch' => ['type' => 'i', 'value' => $inch],
            'type' => ['type' => 's', 'value' => $type],
            'stock' => ['type' => 'i', 'value' => $stock],
            'id_album' => ['type' => 'i', 'value' => $id_album]
        ];

        $setClauses = [];
        $types = '';
        $values = [];

        foreach ($fields as $field => $data) {
            if ($data['value'] !== null) {
                $setClauses[] = "$field = ?";
                $types .= $data['type'];
                $values[] = $data['value'];
            }
        }

        if (empty($setClauses)) {
            return false; // No columns to update
        }

        $query = "UPDATE vinyls SET " . implode(', ', $setClauses) . " WHERE id_vinyl = ?";
        $types .= 'i';
        $values[] = $id_vinyl;

        $result = $this->db->executeQueryAffectRows($query, $types, ...$values);

        if ($result) {
            $this->broadcastCartVinyl($id_vinyl);
            $this->notificateVinylQuantity($id_vinyl);
        }

        return $result;
    }

    function getArtists() {
        $query = "SELECT
            a.id_artist,
            a.name
            FROM artists a";
        $result['artists'] =  $this->db->executeResults($query);
        return $result;
    }
}