<?php
$page_title = "Student List";
$allowed_roles = ['admin'];
include '../includes/header.php';

// Fetch all students using the helper function
$students = get_all_students($pdo);
?>

<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                    <span class="card-title" style="margin-bottom: 0;">
                        <i class="material-icons left blue-text">people</i>Student Directory
                    </span>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div class="input-field" style="width: 280px; margin: 0;">
                            <i class="material-icons prefix">search</i>
                            <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search name, student no, ID...">
                        </div>
                        <a href="register_student.php" class="btn blue darken-2 waves-effect waves-light">
                            <i class="material-icons left">add</i>Register
                        </a>
                    </div>
                </div>
                
                <?php display_session_message('success_message'); ?>
                <?php display_session_message('error_message', 'red'); ?>

                <table class="highlight responsive-table" id="studentTable" style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Student Number</th>
                            <th>National ID</th>
                            <th>Email</th>
                            <th>Active Course</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($student['student_number'] ?: $student['student_id_number']); ?></code></td>
                                    <td><?php echo htmlspecialchars($student['id_number'] ?: '<span class="grey-text">-</span>'); ?></td>
                                    <td><a href="mailto:<?php echo htmlspecialchars($student['email']); ?>"><?php echo htmlspecialchars($student['email']); ?></a></td>
                                    <td><?php echo htmlspecialchars($student['course_of_study']); ?></td>
                                    <td>
                                        <span class="chip <?php echo ($student['academic_status'] == STATUS_ACTIVE) ? 'green lighten-4 green-text text-darken-3' : 'orange lighten-4 orange-text text-darken-3'; ?> tiny">
                                            <?php echo htmlspecialchars($student['academic_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="student_profile.php?id=<?php echo $student['id']; ?>" class="tooltipped" data-position="top" data-tooltip="View Profile">
                                            <i class="material-icons blue-text">visibility</i>
                                        </a>
                                        <a href="update_student.php?id=<?php echo $student['id']; ?>" class="tooltipped" data-position="top" data-tooltip="Edit Record">
                                            <i class="material-icons orange-text">edit</i>
                                        </a>
                                        <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $student['id']; ?>)" class="tooltipped" data-position="top" data-tooltip="Delete Record">
                                            <i class="material-icons red-text">delete</i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="center-align">No students found in database.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function searchTable() {
    var input = document.getElementById("searchInput");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("studentTable");
    var tr = table.getElementsByTagName("tr");

    for (var i = 1; i < tr.length; i++) {
        var tdName = tr[i].getElementsByTagName("td")[0];
        var tdNum = tr[i].getElementsByTagName("td")[1];
        var tdId = tr[i].getElementsByTagName("td")[2];
        var tdCourse = tr[i].getElementsByTagName("td")[4];

        if (tdName || tdNum || tdId || tdCourse) {
            var txtName = tdName ? (tdName.textContent || tdName.innerText) : '';
            var txtNum = tdNum ? (tdNum.textContent || tdNum.innerText) : '';
            var txtId = tdId ? (tdId.textContent || tdId.innerText) : '';
            var txtCourse = tdCourse ? (tdCourse.textContent || tdCourse.innerText) : '';

            if (txtName.toUpperCase().indexOf(filter) > -1 || 
                txtNum.toUpperCase().indexOf(filter) > -1 || 
                txtId.toUpperCase().indexOf(filter) > -1 ||
                txtCourse.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
</script>

<script src="../js/confirmation.js"></script>
<?php include '../includes/footer.php'; ?>