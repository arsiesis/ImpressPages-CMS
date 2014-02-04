<?php


namespace Ip\Internal\Content;


class Job
{
    /**
     * Zone routing.
     *
     * @param $info
     * @return array|null
     * @throws \Ip\Exception
     */
    public static function ipRouteAction_70($info)
    {
        return Model::routePage($info['relativeUri'], $info['request']);
    }
} 
