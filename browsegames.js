async function loadBrowseGames() {
  const res = await fetch("games.json")
  const games = await res.json()
  const four = games.sort(() => Math.random() - 0.5).slice(0, 4)
  const grid = document.getElementById("browse-grid")

  grid.innerHTML = four.map(g => `
    <div class="game-card" data-name="${g.name}">
      <img src="${g.main_image_url}" alt="">
      <div class="game-card-info">
        <h3>${g.name}</h3>
        <p class="secondary-text" style="
          display: -webkit-box;
          -webkit-line-clamp: 3;
          -webkit-box-orient: vertical;
          overflow: hidden;
        ">${g.description}</p>
        <div class="genre-container">
          ${g.genres.map(x => `<p class="genre-badge">${x}</p>`).join("")}
        </div>
      </div>
    </div>
  `).join("")

  document.querySelectorAll(".game-card").forEach(card => {
    const name = card.getAttribute("data-name")
    card.addEventListener("click", () => {
      window.location.href = `details.html?game=${encodeURIComponent(name)}`
    })
  })
}

loadBrowseGames()
