<div id="addToListModal" class="modal hidden">
    <div class="modal-content" style="max-width: 500px;">
        <button class="modal-close">&times;</button>
        
        <h2>Add to List</h2>
        <div id="existingListsSection">
            <p style="color: #aaa; font-size: 14px; margin-bottom: 10px;">Select an existing list:</p>
            <div id="listOptions" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
            </div>
        </div>
        <p style="text-align: center; color: #666; margin: 15px 0;">or</p>
        <div id="createNewListSection">
            <p style="color: #aaa; font-size: 14px; margin-bottom: 10px;">Create a new list:</p>
            <form id="createListForm" class="modal-form" style="margin-bottom: 0;">
                <label>
                    List Name
                    <input type="text" id="newListName" placeholder="Enter list name" required>
                </label>
                <button type="submit" style="width: 100%;">Create & Add</button>
            </form>
        </div>
        
        <p id="addToListError" style="color: #ff6b6b; text-align: center; margin-top: 15px; display: none;"></p>
        <p id="addToListSuccess" style="color: #51cf66; text-align: center; margin-top: 15px; display: none;"></p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const addToListModal = document.getElementById('addToListModal');
    const addToListBtn = document.querySelector('.button-2');
    const modalClose = addToListModal.querySelector('.modal-close');
    const createListForm = document.getElementById('createListForm');
    const errorMsg = document.getElementById('addToListError');
    const successMsg = document.getElementById('addToListSuccess');
    const listOptions = document.getElementById('listOptions');

    
    addToListBtn.addEventListener('click', () => {
        fetch('auth.php', { method: 'GET' })
            .then(res => res.json())
            .catch(() => {
              
                const authModal = document.getElementById('authModal');
                if (authModal) authModal.classList.remove('hidden');
                return;
            });

      
        loadUserLists();
        addToListModal.classList.remove('hidden');
    });

   
    modalClose.addEventListener('click', () => {
        addToListModal.classList.add('hidden');
        errorMsg.style.display = 'none';
        successMsg.style.display = 'none';
    });

  
    window.addEventListener('click', (e) => {
        if (e.target === addToListModal) {
            addToListModal.classList.add('hidden');
        }
    });

   
    function loadUserLists() {
        fetch('components/getUserLists.php')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.lists.length > 0) {
                    listOptions.innerHTML = data.lists.map(list => `
                        <button 
                            type="button" 
                            class="list-option-btn" 
                            data-list-id="${list.id}"
                            style="background-color: #2c3e50; padding: 10px; text-align: left; border-radius: 6px; color: white; font-size: 14px;"
                        >
                            ${escapeHtml(list.name)}
                        </button>
                    `).join('');

                   
                    document.querySelectorAll('.list-option-btn').forEach(btn => {
                        btn.addEventListener('click', () => addToExistingList(btn.dataset.listId));
                    });
                } else {
                    listOptions.innerHTML = '<p style="color: #666; font-size: 13px;">No lists yet. Create one below!</p>';
                }
            })
            .catch(err => {
                console.error('Error loading lists:', err);
                listOptions.innerHTML = '<p style="color: #ff6b6b; font-size: 13px;">Error loading lists</p>';
            });
    }

    function addToExistingList(listId) {
        const gameName = document.querySelector('h1').innerText;
        
        fetch('components/addGameToList.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                list_id: listId,
                game_name: gameName
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Game added to list!');
                    setTimeout(() => addToListModal.classList.add('hidden'), 1500);
                } else {
                    showError(data.message || 'Error adding game to list');
                }
            })
            .catch(err => showError('Error: ' + err.message));
    }

    createListForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const listName = document.getElementById('newListName').value;
        const gameName = document.querySelector('h1').innerText;

        if (!listName.trim()) {
            showError('Please enter a list name');
            return;
        }

        fetch('components/createListAndAddGame.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                list_name: listName,
                game_name: gameName
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showSuccess('List created and game added!');
                    document.getElementById('newListName').value = '';
                    setTimeout(() => addToListModal.classList.add('hidden'), 1500);
                } else {
                    showError(data.message || 'Error creating list');
                }
            })
            .catch(err => showError('Error: ' + err.message));
    });

    function showError(msg) {
        errorMsg.innerText = msg;
        errorMsg.style.display = 'block';
        successMsg.style.display = 'none';
    }

    function showSuccess(msg) {
        successMsg.innerText = msg;
        successMsg.style.display = 'block';
        errorMsg.style.display = 'none';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }
});
</script>
