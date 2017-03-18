<?php
    use Discord\Parts\Channel\Channel;
    use React\Promise\Deferred;
    use React\EventLoop\Timer\Timer;
    use Discord\WebSockets\Event;

    class cmd_music
    {
        private $discord;
        private $message;
        private $maincommand;
        private $submaincommand;
        private $content;

        public function __construct($discord, $message, $maincommand, $content)
        {
            $this->discord = $discord;
            $this->message = $message;
            $this->maincommand = $maincommand;
            $this->content = explode(' ', $content);

            $subcommand = array_shift($this->content);

            $this->submaincommand = $this->discord->commands->getCommandSub($this->maincommand, $subcommand);
            if ($this->message->channel->is_private)
            {

            }
            else
            if (!empty($this->submaincommand) && method_exists($this, "cmd_{$this->submaincommand}"))
            {
                $function = "cmd_{$this->submaincommand}";
                $this->$function();
            }
            else
            {
                array_unshift($this->content, $subcommand);
                new cmd_help($discord, $message, $maincommand, $content);
            }
        }

        private function getVoiceChannel()
        {
            $voicechannels = $this->message->channel->guild->channels->getAll('type', 2);
            foreach ($voicechannels as $voicechannel)
            {
                if (!empty($voicechannel->members->get('user_id', $this->message->author->id)))
                {
                    return $voicechannel;
                }
            }
        }

        private function getVoiceClient()
        {
            $deferred = new Deferred();

            $voicechannel = $this->getVoiceChannel();

            $this->discord->getVoiceClient($voicechannel->guild_id)->then(function ($vc) use ($deferred, $voicechannel)
            {
                $vc->setVolume($this->discord->customguilds->getSettings($this->message)['music']['volume']);
                $deferred->resolve($vc);
            }, function ($e) use ($deferred, $voicechannel)
            {
                $this->discord->joinVoiceChannel($voicechannel)->then(function ($vc) use ($deferred, $voicechannel)
                {
                    $vc->setVolume($this->discord->customguilds->getSettings($this->message)['music']['volume']);
                    $deferred->resolve($vc);
                }, function ($e) use ($deferred)
                {
                    $deferred->reject($e);
                    print_r($e->getMessage());
                });
            });

            return $deferred->promise();
        }

        private function getURL($values)
        {
            $deferred = new Deferred();

            $engine = $restrict = null;

            foreach ($values as $value)
            {
                if (filter_var($value, FILTER_VALIDATE_URL))
                {
                    $deferred->resolve($value);
                    return $deferred->promise();
                }
            }
            foreach ($values as $key => $value)
            {
                if (preg_match('/(--)+(.*)/', $value, $matches))
                {
                    unset($values[$key]);
                    $engine = array_pop($matches);
                    break;
                }
            }

            $engines = ['youtube' => ['y', 'youtube']];

            $engine = array_search($engine, $engines) ? array_search($engine, $engines) : 'youtube';

            $text = implode(' ', $values);

            $search = ['text' => $text, 'engine' => $engine];

            $deferred->reject($search);

            return $deferred->promise();
        }

        private function searchQuery($search)
        {
            $deferred = new Deferred();

            $multipart = [
                            [
                                'name' => 'type',
                                'contents' => 'search'
                            ],
                            [
                                'name' => 'engine',
                                'contents' => $search['engine']
                            ],
                            [
                                'name' => 'text',
                                'contents' => urlencode($search['text'])
                            ]
                        ];
            $headers = ['authorization' => null];
            $options = ['multipart' => $multipart];
            $this->discord->http->post('https://nigtools.com/api.php', null, $headers, false, false, $options)->then(function($response) use ($deferred)
            {
                if (intval($response->code) === 200)
                {
                    $deferred->resolve($response);
                }
                else
                {
                    $deferred->reject('Error getting videos.');
                }
            }, function ($e) use ($deferred)
            {
                print_r($e->getMessage());
                $deferred->reject($e);
            });

            return $deferred->promise();
        }

        private function getJson($url)
        {
            $deferred = new Deferred();

            $url = escapeshellarg($url);

            $data = null;

            $process = new React\ChildProcess\Process("youtube-dl {$url} --dump-single-json");

            $process->on('exit', function($exitCode, $termSignal) use (&$data, $deferred)
            {
                if (intval($exitCode) === 1)
                {
                    $deferred->reject('Invalid Url');
                }
                else
                {
                    $data = json_decode($data);
                    $deferred->resolve($data);
                }
                print_r($exitCode);
                //print_r($termSignal);
                //$this->discord->logger->addInfo('Child Exited');
            });

            $this->discord->loop->addTimer(0.001, function($timer) use (&$data, $deferred, $process)
            {
                $process->start($timer->getLoop());

                $process->stdout->on('data', function($output) use (&$data, $deferred, $timer)
                {
                    $data .= $output;
                });
            });

            return $deferred->promise();
        }

        private function downloadAudio($json)
        {
            $deferred = new Deferred();

            $url = escapeshellarg($json->webpage_url);
            $filename = $json->id . '-' . md5($json->title) . '-' . $json->duration;

            foreach (scandir($this->discord->filedirectory) as $file)
            {
                if (pathinfo($file, PATHINFO_FILENAME) === $filename)
                {
                    $deferred->resolve($file);
                    return $deferred->promise();
                }
            }

            $file = escapeshellcmd($this->discord->filedirectory . $filename . '.%(ext)s');

            $cmd = "youtube-dl {$url} --extract-audio --audio-format best --audio-quality 0 ";
            $cmd .= "--restrict-filenames --output {$file} --no-check-certificate --no-warnings ";
            $cmd .= "--source-address 0.0.0.0";

            $process = new React\ChildProcess\Process($cmd);

            $process->on('exit', function($exitCode, $termSignal) use ($deferred, $filename)
            {
                if (intval($exitCode) !== 0)
                {
                    $deferred->reject('Error downloading video.');
                }
                else
                {
                    foreach (scandir($this->discord->filedirectory) as $file)
                    {
                        if (pathinfo($file, PATHINFO_FILENAME) === $filename)
                        {
                            $deferred->resolve($file);
                        }
                    }
                }
                //$this->discord->logger->addInfo('Child Exited');
            });

            $this->discord->loop->addTimer(0.001, function($timer) use ($deferred, $process)
            {
                $process->start($timer->getLoop());

                $process->stdout->on('data', function($output) use ($deferred, $timer)
                {
                    /*print_r($output);
                    $output = explode('[ffmpeg]', $output);
                    $output = !empty($output[1]) ? explode(' ', $output[1]) : null;
                    $output = !empty($output[2]) ? substr(pathinfo(explode(' ', $output[2])[0], PATHINFO_BASENAME), 0, -1) : null;
                    if ($output)
                    {
                        $deferred->resolve($output);
                    }*/
                });
            });

            return $deferred->promise();
        }

        private function playQueue()
        {
            $this->getVoiceClient()->then(function ($vc)
            {
                $queue = $this->discord->customguilds->getQueue($this->message);
                if (empty($queue['current']))
                {
                    if (!empty($queue['wait']))
                    {
                        $this->discord->customguilds->setQueueNext($this->message)->then(function ($queueFront) use ($vc)
                        {
                            $this->message->channel->sendMessage("Now playing: `{$queueFront['name']}` by `{$queueFront['recommended']}`")->then(function ($message) use ($vc, $queueFront)
                            {
                                $vc->playFile($this->discord->filedirectory . $queueFront['file'])->then(function () use ($message)
                                {
                                    $message->delete();
                                    $queue = $this->discord->customguilds->getQueue($this->message);
                                    if ($queue['current']['repeat']['boolean'])
                                    {
                                        $this->playQueue();
                                    }
                                    else
                                    {
                                        $this->discord->customguilds->unsetQueueNext($this->message)->then(function ()
                                        {
                                            $this->playQueue();
                                        });
                                    }
                                }, function ($e)
                                {
                                    print_r($e->getMessage());
                                });
                            });
                        });
                    }
                }
                else
                {
                    if ($queue['current']['repeat']['boolean'] && !$vc->speaking)
                    {
                        $this->message->channel->sendMessage("Now repeating: `{$queue['current']['name']}` by `{$queue['current']['recommended']}`, repeat by: `{$queue['current']['repeat']['requested']}`")->then(function ($message) use ($vc, $queue)
                        {
                            $vc->playFile($this->discord->filedirectory . $queue['current']['file'])->then(function () use ($message)
                            {
                                $message->delete();
                                $queue = $this->discord->customguilds->getQueue($this->message);
                                if ($queue['current']['repeat']['boolean'])
                                {
                                    $this->playQueue();
                                }
                                else
                                {
                                    $this->discord->customguilds->unsetQueueNext($this->message)->then(function ()
                                    {
                                        $this->playQueue();
                                    });
                                }
                            }, function ($e)
                            {
                                print_r($e->getMessage());
                            });
                        });
                    }
                }
            }, function ($e)
            {

            });
        }

        private function sendReactions($message, $emojis)
        {
            $deferred = new Deferred();

            /*$message->react(array_shift($emojis))->then(function ($message) use ($deferred, $emojis)
            {
                $deferred->resolve($message);
            }, function ($e) use ($deferred)
            {
                $deferred->reject($e);
            });*/

            $emoji = array_shift($emojis);
            $message->react($emoji)->then(function ($message) use ($deferred, $emojis)
            {
                if (!empty($emojis))
                {
                    $emoji = array_shift($emojis);
                    $message->react($emoji)->then(function ($message) use ($deferred, $emojis)
                    {
                        if (!empty($emojis))
                        {
                            $emoji = array_shift($emojis);
                            $message->react($emoji)->then(function ($message) use ($deferred, $emojis)
                            {
                                if (!empty($emojis))
                                {
                                    $emoji = array_shift($emojis);
                                    $message->react($emoji)->then(function ($message) use ($deferred, $emojis)
                                    {
                                        if (!empty($emojis))
                                        {
                                            $emoji = array_shift($emojis);
                                            $message->react($emoji)->then(function ($message) use ($deferred, $emojis)
                                            {
                                                if (!empty($emojis))
                                                {
                                                    $emoji = array_shift($emojis);
                                                    $message->react($emoji)->then(function ($message) use ($deferred, $emojis)
                                                    {
                                                        if (!empty($emojis))
                                                        {
                                                            $emoji = array_shift($emojis);
                                                            $message->react($emoji)->then(function ($message) use ($deferred, $emojis)
                                                            {
                                                                if (!empty($emojis))
                                                                {
                                                                    $emoji = array_shift($emojis);
                                                                    $message->react($emoji)->then(function ($message) use ($deferred, $emojis)
                                                                    {
                                                                        if (!empty($emojis))
                                                                        {
                                                                            $emoji = array_shift($emojis);
                                                                            $message->react($emoji)->then(function ($message) use ($deferred, $emojis)
                                                                            {
                                                                                if (!empty($emojis))
                                                                                {
                                                                                    $emoji = array_shift($emojis);
                                                                                    $message->react($emoji)->then(function ($message) use ($deferred, $emojis)
                                                                                    {
                                                                                        if (!empty($emojis))
                                                                                        {
                                                                                            $emoji = array_shift($emojis);
                                                                                            $message->react($emoji)->then(function ($message) use ($deferred, $emojis)
                                                                                            {
                                                                                                    $deferred->resolve($message);
                                                                                            }, function ($e) use ($deferred)
                                                                                            {
                                                                                                $deferred->reject($e);
                                                                                            });
                                                                                        }
                                                                                        else
                                                                                        {
                                                                                            $deferred->resolve($message);
                                                                                        }
                                                                                    }, function ($e) use ($deferred)
                                                                                    {
                                                                                        $deferred->reject($e);
                                                                                    });
                                                                                }
                                                                                else
                                                                                {
                                                                                    $deferred->resolve($message);
                                                                                }
                                                                            }, function ($e) use ($deferred)
                                                                            {
                                                                                $deferred->reject($e);
                                                                            });
                                                                        }
                                                                        else
                                                                        {
                                                                            $deferred->resolve($message);
                                                                        }
                                                                    }, function ($e) use ($deferred)
                                                                    {
                                                                        $deferred->reject($e);
                                                                    });
                                                                }
                                                                else
                                                                {
                                                                    $deferred->resolve($message);
                                                                }
                                                            }, function ($e) use ($deferred)
                                                            {
                                                                $deferred->reject($e);
                                                            });
                                                        }
                                                        else
                                                        {
                                                            $deferred->resolve($message);
                                                        }
                                                    }, function ($e) use ($deferred)
                                                    {
                                                        $deferred->reject($e);
                                                    });
                                                }
                                                else
                                                {
                                                    $deferred->resolve($message);
                                                }
                                            }, function ($e) use ($deferred)
                                            {
                                                $deferred->reject($e);
                                            });
                                        }
                                        else
                                        {
                                            $deferred->resolve($message);
                                        }
                                    }, function ($e) use ($deferred)
                                    {
                                        $deferred->reject($e);
                                    });
                                }
                                else
                                {
                                    $deferred->resolve($message);
                                }
                            }, function ($e) use ($deferred)
                            {
                                $deferred->reject($e);
                            });
                        }
                        else
                        {
                            $deferred->resolve($message);
                        }
                    }, function ($e) use ($deferred)
                    {
                        $deferred->reject($e);
                    });
                }
                else
                {
                    $deferred->resolve($message);
                }
            }, function ($e) use ($deferred)
            {
                $deferred->reject($e);
            });

            return $deferred->promise();
        }

        public function cmd_join()
        {
            $voicechannel = $this->getVoiceChannel();

            if (!empty($voicechannel))
            {
                $this->discord->joinVoiceChannel($voicechannel)->then(function ($vc) use ($voicechannel)
                {
                    //Bot joined voice channel
                    $this->message->channel->sendMessage("Joined the channel: `{$voicechannel->name}`");
                }, function ($e) use ($voicechannel)
                {
                    $this->discord->getVoiceClient($voicechannel->guild_id)->then(function ($vc) use ($voicechannel)
                    {
                        $vc->switchChannel($voicechannel)->then(function () use ($voicechannel)
                        {
                            //Success, bot switched channels
                            $this->message->channel->sendMessage("Switched to channel: `{$voicechannel->name}`");
                        }, function ($e)
                        {
                            //Bot already in channel?
                            print_r($e->getMessage());
                        });
                    }, function ($e)
                    {
                        //User not in any voice channels
                        print_r($e->getMessage());
                    });
                    //Bot could not join voice channel, either non-existant or already in one
                    print_r($e->getMessage());
                });
            }
            else
            {
                //User not in any voice channels
            }
        }

        public function cmd_volume()
        {
            if (!empty($this->content))
            {
                $volume = intval($this->content[0]);
                if ($volume <= 100 && 1 <= $volume)
                {
                    if ($this->discord->customguilds->setSettings($this->message, ['music' => ['volume' => $volume]]))
                    {
                        $this->discord->getVoiceClient($this->message->channel->guild->id)->then(function ($vc) use ($volume)
                        {
                            $vc->setVolume($volume)->then(function () use ($vc, $volume)
                            {
                                $this->message->channel->sendMessage("Volume set to `{$volume}` by `{$this->message->author->user->username}#{$this->message->author->user->discriminator}`.");
                            }, function ($e)
                            {
                                $this->message->channel->sendMessage("Cannot change volume while bot is playing, future audio will have this volume.");
                                print_r($e->getMessage());
                            });
                        }, function ($e) use ($volume)
                        {
                            $this->message->channel->sendMessage("Volume set to `{$volume}` by `{$this->message->author->user->username}#{$this->message->author->user->discriminator}`.");
                        });
                    }
                    else
                    {
                        $this->message->reply('Error saving guild setting.');
                    }
                }
                else
                if ($volume > 100)
                {
                    $this->message->reply('Outrageous Volume Level!');
                }
                else
                if ($volume <= 0)
                {
                    $this->message->reply('Volume is too quiet.');
                }
                else
                {
                    $this->message->reply('Invalid Volume Level, `1-100`.');
                }
            }
            else
            {
                $volume = $this->discord->customguilds->getSettings($this->message)['music']['volume'];
                $this->message->reply("Current volume is: `{$volume}`");
                //new cmd_help($this->discord, $this->message, $this->maincommand, $this->submaincommand);
            }
        }

        public function cmd_skip()
        {
            $this->discord->getVoiceClient($this->message->channel->guild->id)->then(function ($vc)
            {
                $vc->stop()->then(function ()
                {
                    $this->message->channel->sendMessage("`{$this->message->author->user->username}#{$this->message->author->user->discriminator}` skipped the song.")->then(function ($message)
                    {
                        $this->discord->customguilds->unsetQueueNext($this->message)->then(function ()
                        {
                            $this->playQueue();
                        });
                        $this->discord->loop->addTimer(15, function($timer) use ($message)
                        {
                            $message->delete();
                        });
                    });
                }, function ($e)
                {
                    $this->message->channel->sendMessage("Bot is not playing anything.")->then(function ($message)
                    {
                        $this->discord->loop->addTimer(15, function($timer) use ($message)
                        {
                            $message->delete();
                        });
                    });
                });
            }, function ($e)
            {
                $this->message->channel->sendMessage("Bot's not in a channel.")->then(function ($message)
                {
                    $this->discord->loop->addTimer(15, function($timer) use ($message)
                    {
                        $message->delete();
                    });
                });
            });
        }

        public function cmd_pause()
        {
            $this->discord->getVoiceClient($this->message->channel->guild->id)->then(function ($vc)
            {
                $vc->pause()->then(function ()
                {
                    $this->message->channel->sendMessage("`{$this->message->author->user->username}#{$this->message->author->user->discriminator}` paused the bot.")->then(function ($message)
                    {
                        $this->discord->loop->addTimer(15, function($timer) use ($message)
                        {
                            $message->delete();
                        });
                    });
                }, function ($e)
                {
                    $this->message->channel->sendMessage("Bot is already paused/not playing anything.")->then(function ($message)
                    {
                        $this->discord->loop->addTimer(15, function($timer) use ($message)
                        {
                            $message->delete();
                        });
                    });
                });
            }, function ($e)
            {
                $this->message->channel->sendMessage("Bot's not in a voice channel.")->then(function ($message)
                {
                    $this->discord->loop->addTimer(15, function($timer) use ($message)
                    {
                        $message->delete();
                    });
                });
            });
        }

        public function cmd_unpause()
        {
            $this->discord->getVoiceClient($this->message->channel->guild->id)->then(function ($vc)
            {
                $vc->unpause()->then(function ()
                {
                    $this->message->channel->sendMessage("`{$this->message->author->user->username}#{$this->message->author->user->discriminator}` unpaused the bot.");
                }, function ($e)
                {
                    $this->message->channel->sendMessage("Bot is already unpaused/not playing anything.");
                });
            }, function ($e)
            {
                $this->message->channel->sendMessage("Bot's not in a voice channel.");
            });
        }

        public function cmd_repeat()
        {
            $this->discord->customguilds->setQueueRepeat($this->message)->then(function ($queueFront)
            {
                if ($queueFront['repeat']['boolean'])
                {
                    $this->message->channel->sendMessage("`{$queueFront['repeat']['requested']}` set the song: `{$queueFront['name']}` to repeat.")->then(function ($message)
                    {
                        $this->discord->loop->addTimer(15, function($timer) use ($message)
                        {
                            $message->delete();
                        });
                    });
                }
                else
                {
                    $this->message->channel->sendMessage("Unset repeat for song: `{$queueFront['name']}`")->then(function ($message)
                    {
                        $this->discord->loop->addTimer(15, function($timer) use ($message)
                        {
                            $message->delete();
                        });
                    });
                }
            }, function ($e)
            {
                $this->message->channel->sendMessage("Nothing to repeat.")->then(function ($message)
                {
                    $this->discord->loop->addTimer(15, function($timer) use ($message)
                    {
                        $message->delete();
                    });
                });
            });
        }

        public function cmd_queue()
        {
            $queue = $this->discord->customguilds->getQueue($this->message);
            if (!empty($queue['current']))
            {
                $embed['color']                 = 65280; //green
                $embed['description']           = "Queued by: {$queue['current']['recommended']}";
                $embed['author']['name']        = "Currently playing {$queue['current']['name']}";
                if ($queue['current']['repeat']['boolean'])
                {
                    $embed['description'] = "Queued by: `{$queue['current']['recommended']}` | Set to repeat by: {$queue['current']['repeat']['requested']}";
                }
                else
                {
                    $embed['description'] = "Queued by: `{$queue['current']['recommended']}`";
                }

                foreach ($queue['wait'] as $key => $wait)
                {
                    $key = $key + 1;
                    $embed['fields'][$key]['name'] = "{$key}. {$wait['name']}";
                    $embed['fields'][$key]['value'] = "Queued by: {$wait['recommended']} | URL: {$wait['url']}";
                }

                $embed['thumbnail']['url']      = $queue['current']['thumbnail'];

                $duration = $queue['current']['duration'];
                $hours = floor($duration / 3600);
                $minutes = floor(($duration / 60) % 60);
                $seconds = $duration % 60;
                $duration = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

                echo "$hours:$minutes:$seconds";
                $embed['footer']['text']        = "00:00:00/{$duration}";
            }
            else
            {
                $embed['color']                 = 16711680; //red
                $embed['author']['name']        = "Currently playing nothing.";
                $embed['footer']['text']        = "play something plz";
            }
            $embed['author']['url']         = 'https://nigtools.com/discord/help/';
            $embed['author']['icon_url']    = $this->discord->avatar;
            $embed['footer']['icon_url']    = ($this->message->channel->is_private) ? $this->message->author->avatar : $this->message->channel->guild->icon;
            $embed = $this->discord->factory(\Discord\Parts\Embed\Embed::class, $embed);

            $this->message->channel->sendMessage(null, null, $embed)->then(function ($message)
            {
                $this->discord->loop->addTimer(15, function($timer) use ($message)
                {
                    $message->delete();
                });
            });
        }

        public function cmd_playg()
        {
            if (!empty($this->content))
            {
                $this->getURL($this->content)->then(function ($url)
                {
                    $this->getJson($url)->then(function ($json)
                    {
                        if ($json->duration <= 10800 || in_array($this->message->author->id, $this->discord->owners))
                        {
                            $this->downloadAudio($json)->then(function ($filename) use ($json)
                            {
                                $queue['url'] = $json->webpage_url;
                                $queue['thumbnail'] = $json->thumbnail;
                                $queue['name'] = $json->title;
                                $queue['duration'] = $json->duration;
                                $queue['recommended'] = "{$this->message->author->user->username}#{$this->message->author->user->discriminator}";
                                $queue['file'] = $filename;
                                $this->getVoiceClient()->then(function ($vc) use ($queue)
                                {
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                }, function ($e)
                                {

                                });
                            }, function ($e)
                            {
                                print_r($e);
                                $this->message->channel->sendMessage("Error downloading audio.")->then(function ($message)
                                {
                                    $this->discord->loop->addTimer(15, function($timer) use ($message)
                                    {
                                        $message->delete();
                                    });
                                });
                            });
                            print_r('good');
                        }
                        else
                        {
                            $this->message->channel->sendMessage("The video: `{$json->title}` at <{$json->webpage_url}> is higher than 3 hours. 10800 < {$json->duration}");
                            print_r('bad');
                        }
                    }, function ($e)
                    {
                        //
                        print_r($e);
                    });
                }, function ($search)
                {
                    $this->searchQuery($search)->then(function ($response)
                    {
                        $embed['color'] = rand(1, 16777215);
                        $embed['timestamp'] = null;
                        $embed['author']['name'] = "Youtube results for: {$response->searchterm}";
                        $embed['author']['icon_url'] = $this->discord->avatar;

                        if ($response->amount === 'No results found.')
                        {
                            $embed['description'] = $response->amount;
                            $embed['footer']['text'] = "0 videos found.";
                            $end = 0;
                        }
                        else
                        {
                            $embed['description'] = "About {$response->amount} results.";
                            $amount = count($response->videos);
                            $end = $amount <= 9 ? $amount : 9;
                            for ($i = 0; $i < $end; $i++)
                            {
                                $title = html_entity_decode($response->videos[$i]->title, ENT_QUOTES, 'UTF-8');
                                $num = $i + 1;
                                $embed['fields'][$i]['name'] = "{$num}. {$title}";
                                $embed['fields'][$i]['value'] = "{$response->videos[$i]->duration} | {$response->videos[$i]->views} | {$response->videos[$i]->uploaded} | URL: {$response->videos[$i]->url}";
                            }

                            $embed['footer']['text'] = "{$i} results of the {$amount} videos found.";
                        }

                        $embed['footer']['icon_url'] = $this->message->channel->guild->icon;

                        $embed = $this->discord->factory(\Discord\Parts\Embed\Embed::class, $embed);

                        $this->message->channel->sendMessage(null, null, $embed)->then(function ($message) use ($end)
                        {
                            $this->discord->loop->addTimer(15, function($timer) use ($message)
                            {
                                $message->delete();
                            });

                            $emojis = [];
                            for ($i = 1; $i < ($end + 1); $i++)
                            {
                                $emojis[] = "{$i}âƒ£";
                            }
                            $emojis[] = "ðŸš«";
                            $this->sendReactions($message, $emojis)->then(function ($message) use ($emojis)
                            {
                                $this->discord->on(Event::MESSAGE_REACTION_ADD, function ($reaction) use ($message, $emojis)
                                {
                                    if (in_array($reaction->emoji->name, $emojis) &&
                                        $reaction->message_id === $message->id &&
                                        $reaction->channel_id === $message->channel->id &&
                                        $reaction->user_id === $this->message->author->id
                                    )
                                    {
                                        if ($reaction->emoji->name === "ðŸš«")
                                        {
                                            $message->delete();
                                        }
                                        else
                                        {
                                            $message->delete()->then(function ($oldmessage) use ($reaction, $emojis)
                                            {
                                                $field = array_search($reaction->emoji->name, $emojis);
                                                $video = $oldmessage->embeds[0]->fields[$field]->value;
                                                $video = explode('|', $video);
                                                $video = array_pop($video);
                                                $video = explode(' ', $video);
                                                $video = array_pop($video);
                                                $this->message->channel->broadcastTyping();
                                                $this->getJson($video)->then(function ($json)
                                                {
                                                    if ($json->duration <= 10800 || in_array($this->message->author->id, $this->discord->owners))
                                                    {
                                                        $this->downloadAudio($json)->then(function ($filename) use ($json)
                                                        {
                                                            $queue['url'] = $json->webpage_url;
                                                            $queue['thumbnail'] = $json->thumbnail;
                                                            $queue['name'] = $json->title;
                                                            $queue['duration'] = $json->duration;
                                                            $queue['recommended'] = "{$this->message->author->user->username}#{$this->message->author->user->discriminator}";
                                                            $queue['file'] = $filename;
                                                            $this->getVoiceClient()->then(function ($vc) use ($queue)
                                                            {
                                                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                                            });
                                                            $this->getVoiceClient()->then(function ($vc) use ($queue)
                                                            {
                                                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                                            });
                                                            $this->getVoiceClient()->then(function ($vc) use ($queue)
                                                            {
                                                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                                            });
                                                            $this->getVoiceClient()->then(function ($vc) use ($queue)
                                                            {
                                                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                                            });
                                                            $this->getVoiceClient()->then(function ($vc) use ($queue)
                                                            {
                                                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                                            });
                                                            $this->getVoiceClient()->then(function ($vc) use ($queue)
                                                            {
                                                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                                            });
                                                            $this->getVoiceClient()->then(function ($vc) use ($queue)
                                                            {
                                                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                                            });
                                                            $this->getVoiceClient()->then(function ($vc) use ($queue)
                                                            {
                                                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                                            });
                                                            $this->getVoiceClient()->then(function ($vc) use ($queue)
                                                            {
                                                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                                            });
                                                            $this->getVoiceClient()->then(function ($vc) use ($queue)
                                                            {
                                                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                                            });
                                                            $this->getVoiceClient()->then(function ($vc) use ($queue)
                                                            {
                                                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                                            });
                                                            $this->getVoiceClient()->then(function ($vc) use ($queue)
                                                            {
                                                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                                            });
                                                            $this->getVoiceClient()->then(function ($vc) use ($queue)
                                                            {
                                                                    $vc->playFile($this->discord->filedirectory . $queue['file']);
                                                            });
                                                        }, function ($e)
                                                        {
                                                            print_r($e);
                                                            print_r(PHP_EOL);
                                                            $this->message->channel->sendMessage("Error downloading audio.")->then(function ($message)
                                                            {
                                                                $this->discord->loop->addTimer(15, function($timer) use ($message)
                                                                {
                                                                    $message->delete();
                                                                });
                                                            });
                                                        });
                                                        print_r('good');
                                                    }
                                                    else
                                                    {
                                                        $this->message->channel->sendMessage("The video: `{$json->title}` at <{$json->webpage_url}> is higher than 3 hours. 10800 < {$json->duration}")->then(function ($message)
                                                        {
                                                            $this->discord->loop->addTimer(15, function($timer) use ($message)
                                                            {
                                                                $message->delete();
                                                            });
                                                        });
                                                        print_r('bad');
                                                    }
                                                }, function ($e)
                                                {
                                                    //
                                                    print_r($e);
                                                });
                                            });
                                        }
                                    }
                                });
                            });
                        });
                    }, function ($e)
                    {
                        print_r($e);
                    });
                });
            }
            else
            {
                new cmd_help($this->discord, $this->message, $this->maincommand, $this->submaincommand);
            }
        }

        public function cmd_play()
        {
            if (!empty($this->content))
            {
                $this->getURL($this->content)->then(function ($url)
                {
                    $this->getJson($url)->then(function ($json)
                    {
                        if ($json->duration <= 10800 || in_array($this->message->author->id, $this->discord->owners))
                        {
                            $this->downloadAudio($json)->then(function ($filename) use ($json)
                            {
                                $queue['url'] = $json->webpage_url;
                                $queue['thumbnail'] = $json->thumbnail;
                                $queue['name'] = $json->title;
                                $queue['duration'] = $json->duration;
                                $queue['recommended'] = "{$this->message->author->user->username}#{$this->message->author->user->discriminator}";
                                $queue['file'] = $filename;
                                $this->discord->customguilds->addQueue($this->message, $queue)->then(function() use ($json, $queue)
                                {
                                    $this->message->channel->sendMessage("Enqueued `{$json->title}` by `{$queue['recommended']}`")->then(function ($message)
                                    {
                                        $this->playQueue();
                                        $this->discord->loop->addTimer(15, function($timer) use ($message)
                                        {
                                            $message->delete();
                                        });
                                    });
                                });
                            }, function ($e)
                            {
                                print_r($e);
                                $this->message->channel->sendMessage("Error downloading audio.")->then(function ($message)
                                {
                                    $this->discord->loop->addTimer(15, function($timer) use ($message)
                                    {
                                        $message->delete();
                                    });
                                });
                            });
                            print_r('good');
                        }
                        else
                        {
                            $this->message->channel->sendMessage("The video: `{$json->title}` at <{$json->webpage_url}> is higher than 3 hours. 10800 < {$json->duration}");
                            print_r('bad');
                        }
                    }, function ($e)
                    {
                        //
                        print_r($e);
                    });
                }, function ($search)
                {
                    $this->searchQuery($search)->then(function ($response)
                    {
                        $embed['color'] = rand(1, 16777215);
                        $embed['timestamp'] = null;
                        $embed['author']['name'] = "Youtube results for: {$response->searchterm}";
                        $embed['author']['icon_url'] = $this->discord->avatar;

                        if ($response->amount === 'No results found.')
                        {
                            $embed['description'] = $response->amount;
                            $embed['footer']['text'] = "0 videos found.";
                            $end = 0;
                        }
                        else
                        {
                            $embed['description'] = "About {$response->amount} results.";
                            $amount = count($response->videos);
                            $end = $amount <= 9 ? $amount : 9;
                            for ($i = 0; $i < $end; $i++)
                            {
                                $title = html_entity_decode($response->videos[$i]->title, ENT_QUOTES, 'UTF-8');
                                $num = $i + 1;
                                $embed['fields'][$i]['name'] = "{$num}. {$title}";
                                $embed['fields'][$i]['value'] = "{$response->videos[$i]->duration} | {$response->videos[$i]->views} | {$response->videos[$i]->uploaded} | URL: {$response->videos[$i]->url}";
                            }

                            $embed['footer']['text'] = "{$i} results of the {$amount} videos found.";
                        }

                        $embed['footer']['icon_url'] = $this->message->channel->guild->icon;

                        $embed = $this->discord->factory(\Discord\Parts\Embed\Embed::class, $embed);

                        $this->message->channel->sendMessage(null, null, $embed)->then(function ($message) use ($end)
                        {
                            $this->discord->loop->addTimer(15, function($timer) use ($message)
                            {
                                $message->delete();
                            });

                            $emojis = [];
                            for ($i = 1; $i < ($end + 1); $i++)
                            {
                                $emojis[] = "{$i}âƒ£";
                            }
                            $emojis[] = "ðŸš«";
                            $this->sendReactions($message, $emojis)->then(function ($message) use ($emojis)
                            {
                                $this->discord->on(Event::MESSAGE_REACTION_ADD, function ($reaction) use ($message, $emojis)
                                {
                                    if (in_array($reaction->emoji->name, $emojis) &&
                                        $reaction->message_id === $message->id &&
                                        $reaction->channel_id === $message->channel->id &&
                                        $reaction->user_id === $this->message->author->id
                                    )
                                    {
                                        if ($reaction->emoji->name === "ðŸš«")
                                        {
                                            $message->delete();
                                        }
                                        else
                                        {
                                            $message->delete()->then(function ($oldmessage) use ($reaction, $emojis)
                                            {
                                                $field = array_search($reaction->emoji->name, $emojis);
                                                $video = $oldmessage->embeds[0]->fields[$field]->value;
                                                $video = explode('|', $video);
                                                $video = array_pop($video);
                                                $video = explode(' ', $video);
                                                $video = array_pop($video);
                                                $this->message->channel->broadcastTyping();
                                                $this->getJson($video)->then(function ($json)
                                                {
                                                    if ($json->duration <= 10800 || in_array($this->message->author->id, $this->discord->owners))
                                                    {
                                                        $this->downloadAudio($json)->then(function ($filename) use ($json)
                                                        {
                                                            $queue['url'] = $json->webpage_url;
                                                            $queue['thumbnail'] = $json->thumbnail;
                                                            $queue['name'] = $json->title;
                                                            $queue['duration'] = $json->duration;
                                                            $queue['recommended'] = "{$this->message->author->user->username}#{$this->message->author->user->discriminator}";
                                                            $queue['file'] = $filename;
                                                            $this->discord->customguilds->addQueue($this->message, $queue)->then(function() use ($json, $queue)
                                                            {
                                                                $this->message->channel->sendMessage("Enqueued `{$json->title}` by `{$queue['recommended']}`")->then(function ($message)
                                                                {
                                                                    $this->playQueue();
                                                                    $this->discord->loop->addTimer(15, function($timer) use ($message)
                                                                    {
                                                                        $message->delete();
                                                                    });
                                                                });
                                                            });
                                                        }, function ($e)
                                                        {
                                                            print_r($e);
                                                            print_r(PHP_EOL);
                                                            $this->message->channel->sendMessage("Error downloading audio.")->then(function ($message)
                                                            {
                                                                $this->discord->loop->addTimer(15, function($timer) use ($message)
                                                                {
                                                                    $message->delete();
                                                                });
                                                            });
                                                        });
                                                        print_r('good');
                                                    }
                                                    else
                                                    {
                                                        $this->message->channel->sendMessage("The video: `{$json->title}` at <{$json->webpage_url}> is higher than 3 hours. 10800 < {$json->duration}")->then(function ($message)
                                                        {
                                                            $this->discord->loop->addTimer(15, function($timer) use ($message)
                                                            {
                                                                $message->delete();
                                                            });
                                                        });
                                                        print_r('bad');
                                                    }
                                                }, function ($e)
                                                {
                                                    //
                                                    print_r($e);
                                                });
                                            });
                                        }
                                    }
                                });
                            });
                        });
                    }, function ($e)
                    {
                        print_r($e);
                    });
                });
            }
            else
            {
                new cmd_help($this->discord, $this->message, $this->maincommand, $this->submaincommand);
            }
        }
    }
?>