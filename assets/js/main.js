// Handle comment reply functionality
document.addEventListener('DOMContentLoaded', function() {
    // Toggle reply forms
    document.querySelectorAll('.reply-btn').forEach(button => {
        button.addEventListener('click', function() {
            const commentId = this.dataset.commentId;
            const replyForm = document.getElementById(`reply-form-${commentId}`);
            replyForm.classList.toggle('hidden');
        });
    });
    
    // Submit reply forms
    document.querySelectorAll('.reply-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const commentId = this.dataset.commentId;
            const message = this.querySelector('textarea').value;
            
            if (message.trim() === '') {
                alert('Please enter a reply message');
                return;
            }
            
            // In a real app, you would send this to your server to handle the API call
            console.log(`Replying to comment ${commentId} with message: ${message}`);
            
            // Simulate success
            this.querySelector('textarea').value = '';
            this.classList.add('hidden');
            
            // In a real app, you would refresh the comments section or add the new reply dynamically
            alert('Reply posted successfully!');
        });
    });
});