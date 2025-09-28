<?php

namespace App\Http\Controllers;

use App\Mail\Mailer;
use App\Models\Newsletter;
use App\Models\Newsletter_subscriber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Exception;
use Illuminate\Support\Facades\Log;

class NewsletterController extends Controller
{
    public function index()
    {
        $page_data['newsletters'] = Newsletter::orderBy('id', 'desc')->paginate(10);
        return view("admin.newsletter.index", $page_data);
    }
    public function store(Request $request)
    {
        $data["subject"]     = $request->subject;
        $data["description"] = $request->description;

        Newsletter::insert($data);
        Session::flash('success', get_phrase('Newsletter created successfully'));
        return redirect()->back();
    }

    public function delete($id)
    {
        if (Newsletter::where('id', $id)->doesntExist()) {
            Session::flash('success', get_phrase('Data not found.'));
            return redirect()->back();
        }
        Newsletter::where('id', $id)->delete();
        Session::flash('success', get_phrase('Newsletter deleted successfully.'));
        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        if (Newsletter::where('id', $id)->doesntExist()) {
            Session::flash('success', get_phrase('Data not found.'));
            return redirect()->back();
        }
        $data["subject"]     = $request->subject;
        $data["description"] = $request->description;

        Newsletter::where('id', $id)->update($data);
        Session::flash('success', get_phrase('Newsletter updated successfully.'));
        return redirect()->back();
    }

    public function subscribers(Request $request)
    {
        if ($request->search) {
            $subscribers = Newsletter_subscriber::orderBy('id', 'desc')->where('email', 'like', "%$request->search%")->paginate(20);
        } else {
            $subscribers = Newsletter_subscriber::orderBy('id', 'desc')->paginate(20);
        }
        $page_data['subscribers'] = $subscribers;
        return view("admin.newsletter.subscribers", $page_data);
    }
    public function subscribed_user_delete($id)
    {
        Newsletter_subscriber::where('id', $id)->delete();
        Session::flash('success', get_phrase('subscriber delete successfully'));
        return redirect()->back();
    }

    public function newsletters_form()
    {
        return view('admin.newsletter.send_newsletter');
    }

    public function get_user(Request $request)
    {

        $user = User::where('name', 'LIKE', '%' . $request->searchVal . '%')->get();
        foreach ($user as $row) {
            $response[] = ['id' => $row->id, 'text' => $row->name];
        }
        return json_encode($response);
    }

    public function send_newsletters(Request $request)
    {
        $members = [];
        

        if ($request->send_to == 'all') {
            $users      = User::get();
            $newsletter = Newsletter_subscriber::get();
            $members    = array_merge($users->all(), $newsletter->all());
        } elseif ($request->send_to == 'student') {
            $members = User::where('role', 'student')->get();
        } elseif ($request->send_to == 'instructor') {
            $members = User::where('role', 'instructor')->get();
        } elseif ($request->send_to == 'all_subscriber') {
            $members = Newsletter_subscriber::get();
        } elseif ($request->send_to == 'registered_subscriber') {
            $members = Newsletter_subscriber::join('users', 'newsletter_subscribers.email', '=', 'users.email')->get();
        } elseif ($request->send_to == 'non_registered_subscriber') {
            $members = Newsletter_subscriber::leftJoin('users', 'newsletter_subscribers.email', '=', 'users.email')
                ->whereNull('users.email')
                ->get();
        } elseif ($request->send_to == 'selected_user') {
            Session::flash('error', get_phrase('Please select a user'));
            return redirect()->route('admin.newsletter');
        }

        $memberArray = $members instanceof \Illuminate\Support\Collection ? $members->all() : $members;

        foreach (array_chunk($memberArray, 40) as $chunk) {
            $emails = array_map(fn($member) => $member->email, $chunk);
            $emails = $this->validateEmails($emails);

            $this->send_mail($emails, $request->subject, $request->description);
            sleep(1); // Optional: Rate-limit
        }

        Session::flash('success', get_phrase('Email sent successfully'));
        return redirect()->route('admin.newsletter');
    }

    public function send_mail($user_emails, $subject, $description)
    {
        config([
            'mail.mailers.smtp.transport'  => get_settings('protocol'),
            'mail.mailers.smtp.host'       => get_settings('smtp_host'),
            'mail.mailers.smtp.port'       => get_settings('smtp_port'),
            'mail.mailers.smtp.encryption' => get_settings('smtp_crypto'),
            'mail.mailers.smtp.username'   => get_settings('smtp_from_email'),
            'mail.mailers.smtp.password'   => get_settings('smtp_pass'),
            'mail.from.address'            => get_settings('smtp_from_email'),
            'mail.from.name'               => get_settings('smtp_user'),
        ]);

        $mail_data['subject']     = $subject;
        $mail_data['description'] = $description;

        // $send = Mail::to($user_emails)->send(new Mailer($mail_data));
        // return $send;

        $recipients = is_array($user_emails) ? $user_emails : [$user_emails];
        try {
            return Mail::to(get_settings('smtp_from_email'))->bcc($recipients)->send(new Mailer($mail_data));
        } catch (\Exception $e) {
            // Log the failed recipients or error message
            // \Log::error('Newsletter send failed: ' . $e->getMessage(), [
            //     'recipients' => $recipients,
            // ]);
        }
    }
    
    function validateEmails($emails = [])
    {
        $validEmails = [];

        foreach ($emails as $email) {
            $domain = substr(strrchr($email, "@"), 1);
            if (filter_var($email, FILTER_VALIDATE_EMAIL) && checkdnsrr($domain, 'MX')) {
                $validEmails[] = $email;
            }
        }

        $validEmails = array_filter($validEmails, function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) &&
            !str_contains($email, 'gamail.com') &&
            !str_contains($email, 'gamil.com') &&
            !str_contains($email, 'example.com');
        });

        return $validEmails;
    }
}
