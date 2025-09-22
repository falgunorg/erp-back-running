<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\MessageSent;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;

class ChatController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user = User::find($request->user->id);

            if (!$user) {
                throw new \Exception('User not found');
            }

            $conversations = Conversation::where('user1_id', $user->id)
                    ->orWhere('user2_id', $user->id)
                    ->orderBy('updated_at', 'desc')
                    ->get();

            foreach ($conversations as $conversation) {
                $showUser = $conversation->user1_id == $user->id ? User::find($conversation->user2_id) : User::find($conversation->user1_id);

                if ($showUser) {
                    $conversation->name = $showUser->full_name;
                    $conversation->avatar = url('') . '/profile_pictures/' . $showUser->photo;
                }

                $lastMessage = Message::where('conversation_id', $conversation->id)
                        ->orderBy('created_at', 'desc')
                        ->first();

                if ($lastMessage) {
                    $conversation->info = $lastMessage->message;
                    $sender = \App\Models\User::find($lastMessage->sender);
                    if ($sender) {
                        if ($sender->id == $user->id) {
                            $conversation->lastSenderName = "You";
                        } else {
                            $conversation->lastSenderName = $sender->full_name;
                        }
                    }
                }
            }

            $return['data'] = $conversations;
            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function createConversation(Request $request) {
        try {
            // Validate the request input
            $request->validate([
                'recipient_id' => 'required',
            ]);

            // Find the current user
            $user_id = $request->user->id;

            // Get the recipient ID from the request
            $recipient_id = $request->input('recipient_id');

            // Check if a conversation already exists between the users
            $existingConversation = Conversation::where(function ($query) use ($user_id, $recipient_id) {
                        $query->where('user1_id', $user_id)
                                ->where('user2_id', $recipient_id);
                    })->orWhere(function ($query) use ($user_id, $recipient_id) {
                        $query->where('user1_id', $recipient_id)
                                ->where('user2_id', $user_id);
                    })->first();

            if ($existingConversation) {
                return response()->json([
                            'status' => 'error',
                            'message' => 'A conversation already exists with this recipient. Please search on searchbox',
                                ], 422);
            } else {
                $conversation = new Conversation;
                $conversation->user1_id = $user_id;
                $conversation->user2_id = $recipient_id;
                $conversation->save();

                return response()->json([
                            'status' => 'success',
                            'data' => $conversation,
                                ], 200);
            }
        } catch (\Illuminate\Validation\ValidationException $ex) {
            return response()->json([
                        'status' => 'error',
                        'message' => $ex->validator->errors()->first(),
                            ], 422);
        } catch (\Exception $ex) {
            return response()->json([
                        'status' => 'error',
                        'message' => $ex->getMessage(),
                            ], 500);
        }
    }

    public function showMessages(Request $request) {

        try {
            $statusCode = 422;
            $return = [];
            $conversationId = $request->input('conversation_id');
            // Retrieve messages for the given conversation ID
            $messages = Message::where('conversation_id', $conversationId)->orderBy('created_at', 'asc')->get();

            foreach ($messages as $val) {
                $val->direction = $val->sender == $request->user->id ? 'outgoing' : 'incoming';
                $val->position = 'single';
                $val->sent_time = $val->created_at;
            }
            $return['data'] = $messages;
            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function sendMessage(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            // Validate the request
            $request->validate([
                'message' => 'required|string',
                'conversation_id' => 'required',
            ]);
            $conversationId = $request->input('conversation_id');

            // Create a new message
            $message = new Message();
            $message->conversation_id = $conversationId;
            $message->message = $request->input('message');
            $message->sender = $request->user->id; // Set sender as authenticated user's ID

            if ($message->save()) {
                $conversation = Conversation::find($message->conversation_id);
                $conversation->updated_at = now();
                $conversation->save();
                broadcast(new MessageSent($message))->toOthers();
            }

            $return['data'] = $message;
            $statusCode = 200;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

}
