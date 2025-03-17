<?php
session_start();
if (!isset($_SESSION['Type_compte']) || $_SESSION['Type_compte'] !== 'admin_global') {
    header('Location: page_connexion.php');
    exit();
}

include("connexion.php");

// Récupérer les messages de la session
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';

// Effacer les messages après les avoir récupérés
unset($_SESSION['success']);
unset($_SESSION['error']);

function uploadPhoto($file, $type)
{
    $target_dir = "uploads/" . $type . "/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $filename = uniqid() . "." . $extension;
    $target_file = $target_dir . $filename;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'add_team':
            try {
                $photo_path = null;
                if (isset($_FILES["team_photo"]) && $_FILES["team_photo"]["error"] == 0) {
                    $photo_path = uploadPhoto($_FILES["team_photo"], "teams");
                }

                $stmt = $conn->prepare("INSERT INTO equipes (nom, ville, photo, date_creation, created_at) 
                                      VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $_POST['nom'],
                    $_POST['ville'],
                    $photo_path,
                    $_POST['date_creation']
                ]);
                $_SESSION['success'] = "Équipe ajoutée avec succès";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de l'ajout de l'équipe: " . $e->getMessage();
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();


        case 'add_player':
            try {
                $photo_path = null;
                if (isset($_FILES["player_photo"]) && $_FILES["player_photo"]["error"] == 0) {
                    $photo_path = uploadPhoto($_FILES["player_photo"], "players");
                }

                $stmt = $conn->prepare("INSERT INTO joueurs (equipe_id, nom, prenom, origine, nationalite, 
                                      date_naissance, position, numero_maillot, date_debut, date_fin, photo) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['equipe'],
                    $_POST['nom'],
                    $_POST['prenom'],
                    $_POST['origine'],
                    $_POST['nationalite'],
                    $_POST['date_naissance'],
                    $_POST['position'],
                    $_POST['numero'],
                    $_POST['debut_contrat'],
                    $_POST['fin_contrat'],
                    $photo_path
                ]);
                $_SESSION['success'] = "Joueur ajouté avec succès";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de l'ajout du joueur: " . $e->getMessage();
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();


        case 'add_referee':
            try {
                $stmt = $conn->prepare("INSERT INTO arbitres (nom, grade) VALUES (?, ?)");
                $stmt->execute([$_POST['nom'], $_POST['grade']]);
                $_SESSION['success'] = "Arbitre ajouté avec succès";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de l'ajout de l'arbitre: " . $e->getMessage();
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();


        case 'add_stadium':
            try {
                $stmt = $conn->prepare("INSERT INTO stades (nom, ville, capacite) VALUES (?, ?, ?)");
                $stmt->execute([
                    $_POST['nom'],
                    $_POST['ville'],
                    $_POST['capacite']
                ]);
                $_SESSION['success'] = "Stade ajouté avec succès";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de l'ajout du stade: " . $e->getMessage();
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();


        case 'add_staff':
            try {
                $stmt = $conn->prepare("INSERT INTO staff (equipe_id, nom, prenom, date_naissance, poste, date_debut, date_fin) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['equipe'],
                    $_POST['nom'],
                    $_POST['prenom'],
                    $_POST['date_naissance'],
                    $_POST['poste'],
                    $_POST['date_debut'],
                    $_POST['date_fin']
                ]);
                $_SESSION['success'] = "Staff ajouté avec succès";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de l'ajout du staff: " . $e->getMessage();
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
            ;

        case 'add_admin':
            try {
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO comptes (login, password, nom, prenom, type_compte) 
                                      VALUES (?, ?, ?, ?, 'admin_tournoi')");
                $stmt->execute([$_POST['login'], $hashed_password, $_POST['nom'], $_POST['prenom']]);
                $_SESSION['success'] = "Admin tournoi ajouté avec succès";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de l'ajout de l'admin: " . $e->getMessage();
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
            ;

        case 'remove_referee':
            try {
                $stmt = $conn->prepare("DELETE FROM Arbitre WHERE id = ?");
                $stmt->execute([$_POST['referee_id']]);
                $_SESSION['success'] = "Arbitre supprimé avec succès";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression de l'arbitre: " . $e->getMessage();
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();

        case 'remove_stadium':
            try {
                $stmt = $conn->prepare("DELETE FROM Stade WHERE id = ?");
                $stmt->execute([$_POST['stadium_id']]);
                $_SESSION['success'] = "Stade supprimé avec succès";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression du stade: " . $e->getMessage();
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();


        case 'remove_staff':
            try {
                $stmt = $conn->prepare("DELETE FROM Staff WHERE id = ?");
                $stmt->execute([$_POST['staff_id']]);
                $_SESSION['success'] = "Membre du staff supprimé avec succès";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression du membre du staff: " . $e->getMessage();
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();

        case 'remove_admin':
            try {
                $stmt = $conn->prepare("DELETE FROM Compte WHERE id_compte = ? AND Type_compte = 'admin_tournoi'");
                $stmt->execute([$_POST['admin_id']]);
                $_SESSION['success'] = "Admin tournoi supprimé avec succès";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression de l'admin: " . $e->getMessage();
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();


        case 'remove_team':
            try {
                $stmt = $conn->prepare("SELECT photo FROM equipes WHERE id = ?");
                $stmt->execute([$_POST['team_id']]);
                $photo = $stmt->fetchColumn();

                if ($photo && file_exists($photo)) {
                    unlink($photo);
                }

                $stmt = $conn->prepare("DELETE FROM equipes WHERE id = ?");
                $stmt->execute([$_POST['team_id']]);
                $_SESSION['success'] = "Équipe supprimée avec succès";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression de l'équipe: " . $e->getMessage();
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();


        case 'remove_player':
            try {
                $stmt = $conn->prepare("SELECT photo FROM joueurs WHERE id = ?");
                $stmt->execute([$_POST['player_id']]);
                $photo = $stmt->fetchColumn();

                if ($photo && file_exists($photo)) {
                    unlink($photo);
                }

                $stmt = $conn->prepare("DELETE FROM joueurs WHERE id = ?");
                $stmt->execute([$_POST['player_id']]);
                $_SESSION['success'] = "Joueur supprimé avec succès";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression du joueur: " . $e->getMessage();
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();

    }
}

// Fetch teams for dropdowns
$teams = $conn->query("SELECT * FROM equipes")->fetchAll(); // Changed from Equipe
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration Globale</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="sync.js"></script>
</head>

<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Administration Globale</h1>
            <a href="deconnexion.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Déconnexion</a>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Menu -->
        <div class="flex mb-8 space-x-4">
            <button onclick="showForm('team')"
                class="menu-btn bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Ajouter Équipe</button>
            <button onclick="showForm('player')"
                class="menu-btn bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Ajouter Joueur</button>
            <button onclick="showForm('referee')"
                class="menu-btn bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Ajouter Arbitre</button>
            <button onclick="showForm('stadium')"
                class="menu-btn bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Ajouter Stade</button>
            <button onclick="showForm('staff')"
                class="menu-btn bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Ajouter Staff</button>
            <button onclick="showForm('admin')"
                class="menu-btn bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Créer Admin Tournoi</button>
        </div>

        <!-- Forms Container -->
        <div id="formsContainer" class="bg-white p-6 rounded-lg shadow-md">
            <!-- Team Form -->
            <div id="teamForm" class="form-section hidden">
                <h2 class="text-xl font-semibold mb-4">Ajouter une équipe</h2>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="add_team">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom</label>
                            <input type="text" name="nom" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ville</label>
                            <input type="text" name="ville" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date de création</label>
                            <input type="date" name="date_creation" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Photo</label>
                            <input type="file" name="team_photo" accept="image/*" class="mt-1 block w-full">
                        </div>
                    </div>
                    <button type="submit"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Ajouter</button>
                </form>
                <div class="mt-8 pt-8 border-t">
                    <h3 class="text-lg font-semibold mb-4">Supprimer une équipe</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="remove_team">
                        <select name="team_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>">
                                    <?php echo htmlspecialchars($team['nom'] . ' (' . $team['ville'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit"
                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Supprimer</button>
                    </form>
                </div>
            </div>

            <!-- Player Form -->
            <div id="playerForm" class="form-section hidden">
                <h2 class="text-xl font-semibold mb-4">Ajouter un joueur</h2>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="add_player">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Équipe</label>
                            <select name="equipe" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['id']; ?>">
                                        <?php echo htmlspecialchars($team['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom</label>
                            <input type="text" name="nom" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Prénom</label>
                            <input type="text" name="prenom" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Origine</label>
                            <input type="text" name="origine" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nationalité</label>
                            <input type="text" name="nationalite" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date de naissance</label>
                            <input type="date" name="date_naissance" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Position</label>
                            <select name="position" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="gardien">Gardien</option>
                                <option value="defenseur_central">Défenseur Central</option>
                                <option value="defenseur_lateral">Défenseur Latéral</option>
                                <option value="milieu_defensif">Milieu Défensif</option>
                                <option value="milieu_offensif">Milieu Offensif</option>
                                <option value="ailier_droit">Ailier Droit</option>
                                <option value="ailier_gauche">Ailier Gauche</option>
                                <option value="attaquant">Attaquant</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Numéro maillot</label>
                            <input type="number" name="numero" required min="1" max="99"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Début contrat</label>
                            <input type="date" name="debut_contrat" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fin contrat</label>
                            <input type="date" name="fin_contrat" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Photo</label>
                            <input type="file" name="player_photo" accept="image/*" class="mt-1 block w-full">
                        </div>
                    </div>
                    <button type="submit"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Ajouter</button>
                </form>
                <div class="mt-8 pt-8 border-t">
                    <h3 class="text-lg font-semibold mb-4">Supprimer un joueur</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="remove_player">
                        <select name="player_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <?php
                            $players = $conn->query("SELECT j.id as id_joueur, j.nom, j.prenom, e.nom as equipe 
                                                   FROM joueurs j 
                                                   JOIN equipes e ON j.equipe_id = e.id")->fetchAll(); // Changed table and column names
                            foreach ($players as $player):
                                ?>
                                <option value="<?php echo $player['id_joueur']; ?>">
                                    <?php echo htmlspecialchars($player['nom'] . ' ' . $player['prenom'] . ' (' . $player['equipe'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit"
                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Supprimer</button>
                    </form>
                </div>
            </div>

            <!-- Referee Form -->
            <div id="refereeForm" class="form-section hidden">
                <h2 class="text-xl font-semibold mb-4">Ajouter un arbitre</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_referee">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom</label>
                            <input type="text" name="nom" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Grade</label>
                            <input type="text" name="grade" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                placeholder="Ex: Elite, International, National">
                        </div>
                    </div>
                    <button type="submit"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Ajouter</button>
                </form>

                <!-- Remove Referee Section -->
                <div class="mt-8 pt-8 border-t">
                    <h3 class="text-lg font-semibold mb-4">Supprimer un arbitre</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="remove_referee">
                        <select name="referee_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <?php
                            $referees = $conn->query("SELECT id, nom FROM arbitres")->fetchAll(); // Changed from Arbitre
                            foreach ($referees as $referee):
                                ?>
                                <option value="<?php echo $referee['id']; ?>">
                                    <?php echo htmlspecialchars($referee['nom']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit"
                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Supprimer</button>
                    </form>
                </div>
            </div>

            <!-- Stadium Form -->
            <div id="stadiumForm" class="form-section hidden">
                <h2 class="text-xl font-semibold mb-4">Ajouter un stade</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_stadium">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom</label>
                            <input type="text" name="nom" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ville</label>
                            <input type="text" name="ville" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Capacité</label>
                            <input type="number" name="capacite" required min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Ajouter</button>
                </form>
                <!-- Remove Stadium Section -->
                <div class="mt-8 pt-8 border-t">
                    <h3 class="text-lg font-semibold mb-4">Supprimer un stade</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="remove_stadium">
                        <select name="stadium_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <?php
                            $stades = $conn->query("SELECT id, nom FROM stades")->fetchAll(); // Changed from Stade
                            foreach ($stades as $stade):
                                ?>
                                <option value="<?php echo $stade['id']; ?>"><?php echo htmlspecialchars($stade['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit"
                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Supprimer</button>
                    </form>
                </div>
            </div>

            <!-- Staff Form -->
            <div id="staffForm" class="form-section hidden">
                <h2 class="text-xl font-semibold mb-4">Ajouter un membre du staff</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_staff">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Équipe</label>
                            <select name="equipe" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['id']; ?>">
                                        <?php echo htmlspecialchars($team['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom</label>
                            <input type="text" name="nom" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Prénom</label>
                            <input type="text" name="prenom" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date de naissance</label>
                            <input type="date" name="date_naissance" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Poste</label>
                            <select name="poste" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="Entraineur_principal">Entraîneur Principal</option>
                                <option value="Entraineur_adjoint">Entraîneur Adjoint</option>
                                <option value="Preparateur_physique">Préparateur Physique</option>
                                <option value="Medecin">Médecin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Début contrat</label>
                            <input type="date" name="date_debut" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fin contrat</label>
                            <input type="date" name="date_fin" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                    <button type="submit"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Ajouter</button>
                </form>
                <!-- Remove Staff Section -->
                <div class="mt-8 pt-8 border-t">
                    <h3 class="text-lg font-semibold mb-4">Supprimer un membre du staff</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="remove_staff">
                        <select name="staff_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <?php
                            $staffs = $conn->query("SELECT s.id, s.nom, s.prenom, e.nom as equipe FROM staff s JOIN equipes e ON s.equipe_id = e.id")->fetchAll(); // Changed foreign key reference
                            foreach ($staffs as $staff):
                                ?>
                                <option value="<?php echo $staff['id']; ?>">
                                    <?php echo htmlspecialchars($staff['nom'] . ' ' . $staff['prenom'] . ' (' . $staff['equipe'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit"
                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Supprimer</button>
                    </form>
                </div>
            </div>

            <!-- Admin Form -->
            <div id="adminForm" class="form-section hidden">
                <h2 class="text-xl font-semibold mb-4">Créer un compte admin tournoi</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_admin">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Login</label>
                            <input type="text" name="login" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mot de passe</label>
                            <input type="password" name="password" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom</label>
                            <input type="text" name="nom" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Prénom</label>
                            <input type="text" name="prenom" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                    <button type="submit"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Créer</button>
                </form>
                <!-- Remove Admin Section -->
                <div class="mt-8 pt-8 border-t">
                    <h3 class="text-lg font-semibold mb-4">Supprimer un admin tournoi</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="remove_admin">
                        <select name="admin_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <?php
                            $admins = $conn->query("SELECT id_compte, login, nom, prenom FROM comptes WHERE type_compte = 'admin_tournoi'")->fetchAll(); // Changed from Compte
                            foreach ($admins as $admin):
                                ?>
                                <option value="<?php echo $admin['id_compte']; ?>">
                                    <?php echo htmlspecialchars($admin['login'] . ' (' . $admin['nom'] . ' ' . $admin['prenom'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit"
                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showForm(formType) {
            // Hide all forms
            document.querySelectorAll('.form-section').forEach(form => {
                form.classList.add('hidden');
            });

            // Show selected form
            document.getElementById(formType + 'Form').classList.remove('hidden');

            // Update active button style
            document.querySelectorAll('.menu-btn').forEach(btn => {
                btn.classList.remove('bg-green-500');
                btn.classList.add('bg-blue-500');
            });
            event.target.classList.remove('bg-blue-500');
            event.target.classList.add('bg-green-500');
        }

        // Show team form by default
        document.addEventListener('DOMContentLoaded', function () {
            showForm('team');
        });
    </script>
</body>

</html>