<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\FileUploader;
use App\Models\SubmittedAssignment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function assignment_store(Request $request, $course_id)
    {
        $request->validate([
            'title'       => 'required|max:255',
            'questions'   => 'required',
            'total_marks' => 'required',
            'deadline'    => 'required',
            'status'      => 'required|in:active,draft',
        ]);

        $questionFilePath = FileUploader::upload($request->question_file, 'uploads/assignment_files');

        $assignment                = new Assignment;
        $assignment->title         = $request->title;
        $assignment->course_id     = $course_id;
        $assignment->questions     = $request->questions;
        $assignment->total_marks   = $request->total_marks;
        $assignment->deadline      = $request->deadline;
        $assignment->note          = $request->note;
        $assignment->status        = $request->status;
        $assignment->question_file = $questionFilePath;
        $assignment->save();

        return redirect()->back()->with('success', get_phrase('Assignment added successfully'));
    }

    public function assignment_delete($id = null)
    {
        $assignment = Assignment::find($id);
        $assignment->delete();

        return redirect()->back()->with('success', get_phrase('Assignment deleted successfully'));
    }

    public function assignment_update(Request $request)
    {
        $request->validate([
            'title'       => 'required|max:255',
            'questions'   => 'required',
            'total_marks' => 'required',
            'deadline'    => 'required',
        ]);

        $questionFilePath = FileUploader::upload($request->question_file, 'uploads/assignment_files');

        $assignment                = Assignment::find($request->id);
        $assignment->title         = $request->title;
        $assignment->questions     = $request->questions;
        $assignment->total_marks   = $request->total_marks;
        $assignment->deadline      = $request->deadline;
        $assignment->note          = $request->note;
        $assignment->question_file = $questionFilePath;
        $assignment->save();

        return redirect()->back()->with('success', get_phrase('Assignment updated successfully'));
    }

    public function assignment_status($type, $id)
    {
        if ($type == 'active') {
            Assignment::where('id', $id)->update(['status' => 'active']);
        } else {
            Assignment::where('id', $id)->update(['status' => 'draft']);
        }

        return redirect()->back()->with('success', get_phrase('Assignment status changed successfully'));
    }

    public function assignment_submit(Request $request)
    {
        $user_id       = $request->user_id;
        $assignment_id = $request->assignment_id;

        $query = SubmittedAssignment::where('user_id', $user_id)
            ->where('assignment_id', $assignment_id);

        if ($query->exists()) {
            $query->delete();
        }

        $request->validate([
            'answer' => 'required',
        ]);

        $filePath = FileUploader::upload($request->file, 'uploads/submitted_assignment_files');

        $assignment                = new SubmittedAssignment;
        $assignment->user_id       = $request->user_id;
        $assignment->assignment_id = $request->assignment_id;
        $assignment->answer        = $request->answer;
        $assignment->file          = $filePath;
        $assignment->note          = $request->note;
        $assignment->save();

        return redirect()->back()->with('success', get_phrase('Assignment submitted successfully'));
    }

    public function assignment_review(Request $request)
    {
        $request->validate([
            'marks'   => 'required|integer|min:0',
            'remarks' => 'nullable|string',
        ]);

        $assignment          = SubmittedAssignment::find($request->submitted_assignment);
        $assignment->status  = $request->status;
        $assignment->marks   = $request->marks;
        $assignment->remarks = $request->remarks;
        $assignment->save();

        return response()->json([
            'success'      => 'Marks submitted successfully!',
            'redirect_url' => route('view', [
                'path'      => 'admin.course.assignment_submission',
                'id'        => $request->assignment_id,
                'course_id' => $request->course_id,
            ]),
        ]);
    }

    public function download_result($id)
    {
        $page_data['assignment']           = Assignment::findOrFail($id);
        $page_data['submitted_assignment'] = SubmittedAssignment::where('assignment_id', $id)
            ->where('user_id', auth()->user()->id)
            ->first();

        $pdf = Pdf::loadView('course_player.assignment.remark', $page_data);

        return $pdf->download('result.pdf');
    }
}
