<?php

namespace Scanner;

class Custom extends Scanner
{
    public function get_active($eids, $minIv, $minLevel, $exMinIv, $bigKarp, $tinyRat, $swLat, $swLng, $neLat, $neLng, $tstamp = 0, $oSwLat = 0, $oSwLng = 0, $oNeLat = 0, $oNeLng = 0, $encId = 0)
    {
        return array();
    }

    public function get_active_by_id($ids, $minIv, $minLevel, $exMinIv, $bigKarp, $tinyRat, $swLat, $swLng, $neLat, $neLng)
    {
        return array();
    }

    public function query_active($select, $conds, $params, $encSql = '')
    {
        return array();
    }

    public function get_stops($swLat, $swLng, $neLat, $neLng, $tstamp = 0, $oSwLat = 0, $oSwLng = 0, $oNeLat = 0, $oNeLng = 0, $lured = false)
    {
        return array();
    }

    public function query_stops($conds, $params)
    {
        return array();
    }

    public function get_spawnpoints($swLat, $swLng, $neLat, $neLng, $tstamp = 0, $oSwLat = 0, $oSwLng = 0, $oNeLat = 0, $oNeLng = 0)
    {
        return array();
    }

    private function query_spawnpoints($conds, $params)
    {
        return array();
    }

    public function get_recent($swLat, $swLng, $neLat, $neLng, $tstamp = 0, $oSwLat = 0, $oSwLng = 0, $oNeLat = 0, $oNeLng = 0)
    {
        return array();
    }

    public function get_gyms($swLat, $swLng, $neLat, $neLng, $exEligible = false, $tstamp = 0, $oSwLat = 0, $oSwLng = 0, $oNeLat = 0, $oNeLng = 0)
    {
        $conds = array();
        $params = array();

        $conds[] = "lat > :swLat AND lon > :swLng AND lat < :neLat AND lon < :neLng";
        $params[':swLat'] = $swLat;
        $params[':swLng'] = $swLng;
        $params[':neLat'] = $neLat;
        $params[':neLng'] = $neLng;

        if ($oSwLat != 0) {
            $conds[] = "NOT (lat > :oswLat AND lon > :oswLng AND lat < :oneLat AND lon < :oneLng)";
            $params[':oswLat'] = $oSwLat;
            $params[':oswLng'] = $oSwLng;
            $params[':oneLat'] = $oNeLat;
            $params[':oneLng'] = $oNeLng;
        }

        return $this->query_gyms($conds, $params);
    }

    public function get_gym($gymId)
    {
        $conds = array();
        $params = array();

        $conds[] = "id = :gymId";
        $params[':gymId'] = $gymId;

        $gyms = $this->query_gyms($conds, $params);
        $gym = $gyms[0];
        return $gym;
    }

    public function query_gyms($conds, $params)
    {
        global $db;

        $query = "SELECT id AS gym_id,
        lat AS latitude,
        lon AS longitude,
        level AS raid_level,
        pokemon_id AS raid_pokemon_id,
        start_time AS raid_start,
        end_time AS raid_end,
        UNIX_TIMESTAMP(last_modified) AS last_modified,
        gym AS name
        FROM raids
        WHERE :conditions";

        $query = str_replace(":conditions", join(" AND ", $conds), $query);
        $gyms = $db->query($query, $params)->fetchAll(\PDO::FETCH_ASSOC);

        $data = array();
        $i = 0;
        foreach ($gyms as $gym) {
            $guard_pid = null;
            $gym["guard_pokemon_id"] = null;

            $raid_pid = $gym["raid_pokemon_id"];
            if ($raid_pid == "0") {
                $raid_pid = null;
                $gym["raid_pokemon_id"] = null;
            }
            $gym["team_id"] = 0;
            $gym["pokemon"] = [];
            $gym["guard_pokemon_name"] = null;
            $gym["raid_pokemon_name"] = empty($raid_pid) ? null : i8ln($this->data[$raid_pid]["name"]);
            $gym["latitude"] = floatval($gym["latitude"]);
            $gym["longitude"] = floatval($gym["longitude"]);
            $gym["slots_available"] = 0;
            $gym["last_modified"] = $gym["last_modified"] * 1000;
            $gym["raid_start"] = $gym["raid_start"] * 1000;
            $gym["raid_end"] = $gym["raid_end"] * 1000;
            $data[] = $gym;

            unset($gyms[$i]);
            $i++;
        }
        return $data;
    }

    public function get_gyms_api($swLat, $swLng, $neLat, $neLng)
    {
        $conds = array();
        $params = array();

        $conds[] = "lat > :swLat AND lon > :swLng AND lat < :neLat AND lon < :neLng";
        $params[':swLat'] = $swLat;
        $params[':swLng'] = $swLng;
        $params[':neLat'] = $neLat;
        $params[':neLng'] = $neLng;

        global $sendRaidData;
        if (!$sendRaidData) {
            return $this->query_gyms_api($conds, $params);
        } else {
            return $this->query_raids_api($conds, $params);
        }
    }

    public function query_gyms_api($conds, $params)
    {
        global $db;

        $query = "SELECT id AS gym_id,
        lat AS latitude,
        lon AS longitude
        FROM raids
        WHERE :conditions";

        $query = str_replace(":conditions", join(" AND ", $conds), $query);
        $gyms = $db->query($query, $params)->fetchAll(\PDO::FETCH_ASSOC);

        $data = array();
        $i = 0;

        foreach ($gyms as $gym) {
            $gym["latitude"] = floatval($gym["latitude"]);
            $gym["longitude"] = floatval($gym["longitude"]);
            $data[] = $gym;

            unset($gyms[$i]);
            $i++;
        }
        return $data;
    }

    public function query_raids_api($conds, $params)
    {
        global $db;

        $query = "SELECT id AS gym_id,
        lat AS latitude,
        lon AS longitude,
        level AS raid_level,
        pokemon_id AS raid_pokemon_id,
        start_time AS raid_start,
        end_time AS raid_end,
        FROM raids
        WHERE :conditions";

        $query = str_replace(":conditions", join(" AND ", $conds), $query);
        $gyms = $db->query($query, $params)->fetchAll(\PDO::FETCH_ASSOC);

        $data = array();
        $i = 0;

        foreach ($gyms as $gym) {
            $gym["latitude"] = floatval($gym["latitude"]);
            $gym["longitude"] = floatval($gym["longitude"]);
            $data[] = $gym;

            unset($gyms[$i]);
            $i++;
        }
        return $data;
    }

    public function get_weather_by_cell_id($cell_id)
    {
        return null;
    }

    public function get_weather($updated = null)
    {
        return array();
    }
}
