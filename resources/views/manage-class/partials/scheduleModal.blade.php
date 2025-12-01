<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleModalLabel">Add Schedule Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="scheduleModalForm">
                <input type="hidden" id="schedule_id" name="schedule_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="day_name" class="form-label">Day</label>
                        <select class="form-select" id="day_name" name="day_name" required>
                            <option value="">Select Day</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                        </select>
                        <div class="invalid-feedback" id="day_name-error"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_time" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                            <div class="invalid-feedback" id="start_time-error"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_time" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                            <div class="invalid-feedback" id="end_time-error"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="subject_id" class="form-label">Subject</label>
                        <select class="form-select" id="subject_id" name="subject_id" required style="width: 100%;">
                        </select>
                        <div class="invalid-feedback" id="subject_id-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Teacher</label>
                        <select class="form-select" id="user_id" name="user_id" required style="width: 100%;">
                        </select>
                        <div class="invalid-feedback" id="user_id-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="scheduleSubmitBtn">Save Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>
