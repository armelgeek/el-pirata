<?php

namespace App\Http\Controllers;

use App\Models\Enigma;
use App\Models\UserProgress;
use App\Models\Achievement;
use App\Models\Chapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EnigmaController extends Controller
{
    public function index(Request $request)
    {
        $query = Enigma::query();

        // Filtrage
        if ($request->filter === 'non-resolues') {
            $query->whereDoesntHave('userProgress', function($q) {
                $q->where('user_id', Auth::id());
            });
        } elseif ($request->filter === 'completees') {
            $query->whereHas('userProgress', function($q) {
                $q->where('user_id', Auth::id());
            });
        }

        // Filtrage par chapitre
        if ($request->chapter) {
            $query->where('chapter_id', $request->chapter);
        }

        // Recherche
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Pagination avec conservation des paramètres de requête
        $enigmas = $query->orderBy('chapter_id')->orderBy('order')->paginate(6);
        $enigmas->appends($request->all());

        // Récupérer les chapitres pour le filtre
        $chapters = Chapter::orderBy('order')->get();

        return view('enigmas.index', compact('enigmas', 'chapters'));
    }

    public function show(Enigma $enigma)
    {
        $userProgress = UserProgress::firstOrNew([
            'user_id' => Auth::id(),
            'enigma_id' => $enigma->id
        ]);

        if (!$userProgress->first_viewed_at) {
            $userProgress->first_viewed_at = now();
            $userProgress->save();
        }

        // Vérifier les prérequis
        $canAttempt = true;
        $requiredEnigmas = [];
        
        if ($enigma->required_enigmas) {
            $requiredEnigmas = Enigma::whereIn('id', $enigma->required_enigmas)->get();
            foreach ($requiredEnigmas as $required) {
                if (!$required->isCompletedByUser(Auth::id())) {
                    $canAttempt = false;
                    break;
                }
            }
        }

        return view('enigmas.show', compact('enigma', 'userProgress', 'canAttempt', 'requiredEnigmas'));
    }

    public function verify(Request $request, Enigma $enigma)
    {
        $request->validate([
            'answer' => 'required|string'
        ]);

        $userProgress = UserProgress::firstOrNew([
            'user_id' => Auth::id(),
            'enigma_id' => $enigma->id
        ]);

        // Mettre à jour les statistiques
        $userProgress->attempts = ($userProgress->attempts ?? 0) + 1;
        $userProgress->user_answer = $request->answer;
        
        // Calculer le temps passé
        if ($userProgress->first_viewed_at) {
            $timeSpent = Carbon::now()->diffInSeconds($userProgress->first_viewed_at);
            $userProgress->time_spent = $timeSpent;
        }

        // Sauvegarder l'historique des tentatives
        $attemptHistory = $userProgress->attempt_history ?? [];
        $attemptHistory[] = [
            'answer' => $request->answer,
            'timestamp' => now()->toDateTimeString(),
            'time_spent' => $userProgress->time_spent
        ];
        $userProgress->attempt_history = $attemptHistory;

        $isCorrect = strtolower(trim($request->answer)) === strtolower(trim($enigma->answer));

        if ($isCorrect) {
            $userProgress->completed = true;
            $userProgress->completed_at = now();
            
            // Calcul des points en fonction de plusieurs facteurs
            $points = $this->calculatePoints($userProgress, $enigma);
            
            $user = Auth::user();
            $user->points = ($user->points ?? 0) + $points;
            $user->save();

            // Vérifier la progression du chapitre
            if ($enigma->chapter_id) {
                $this->checkChapterProgress($enigma->chapter_id);
            }

            // Vérifier les succès débloqués
            $this->checkAchievements($user, $enigma);

            $message = "Félicitations ! Vous avez résolu l'énigme" . 
                      ($userProgress->attempts == 1 ? " du premier coup !" : " en $userProgress->attempts tentatives !") .
                      "\nVous gagnez $points points !";
        } else {
            $message = $this->getFailureMessage($userProgress->attempts);
        }

        $userProgress->save();

        return response()->json([
            'success' => $isCorrect,
            'message' => $message,
            'points' => $isCorrect ? $points : 0,
            'attempts' => $userProgress->attempts
        ]);
    }

    private function calculatePoints(UserProgress $progress, Enigma $enigma)
    {
        // Points de base selon la difficulté
        $basePoints = $enigma->points;

        // Multiplicateur selon le nombre de tentatives
        $attemptMultiplier = match($progress->attempts) {
            1 => 1.0,    // 100% des points pour la première tentative
            2 => 0.75,   // 75% pour la deuxième
            3 => 0.5,    // 50% pour la troisième
            default => 0.25 // 25% pour les tentatives suivantes
        };

        // Bonus pour rapidité (si résolu en moins de 5 minutes)
        $timeBonus = 0;
        if ($progress->time_spent < 300) { // 5 minutes
            $timeBonus = $basePoints * 0.2; // 20% bonus
        }

        // Bonus pour énigme bonus
        if ($enigma->is_bonus) {
            $basePoints *= 1.5;
        }

        // Calcul final
        return round(($basePoints * $attemptMultiplier) + $timeBonus);
    }

    private function getFailureMessage($attempts)
    {
        return match($attempts) {
            1 => "Première tentative échouée... Réessayez !",
            2 => "Pas encore... Concentrez-vous et réessayez !",
            3 => "Troisième tentative... Regardez bien tous les indices !",
            4 => "N'abandonnez pas ! Peut-être devriez-vous utiliser un indice ?",
            default => "Persévérez, chaque erreur vous rapproche de la solution !"
        };
    }

    private function checkChapterProgress($chapterId)
    {
        $chapter = Chapter::find($chapterId);
        $user = Auth::user();

        // Vérifier si toutes les énigmes du chapitre sont complétées
        $allEnigmasCompleted = $chapter->enigmas()
            ->whereDoesntHave('userProgress', function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('completed', true);
            })
            ->count() === 0;

        if ($allEnigmasCompleted) {
            $userProgress = $chapter->userProgress()
                ->firstOrCreate(['user_id' => $user->id]);

            if (!$userProgress->completed) {
                $userProgress->completed = true;
                $userProgress->completed_at = now();
                $userProgress->save();

                // Bonus de points pour complétion du chapitre
                $chapterBonus = 100; // Points bonus pour avoir complété le chapitre
                $user->points += $chapterBonus;
                $user->save();
            }
        }
    }

    private function checkAchievements($user, $enigma)
    {
        // Vérifier les succès liés aux énigmes
        $achievements = Achievement::where('type', 'enigma_completion')
            ->whereDoesntHave('users', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        foreach ($achievements as $achievement) {
            $requirements = json_decode($achievement->requirements, true);
            
            // Exemple de vérification pour différents types de succès
            $shouldUnlock = match($requirements['condition']) {
                'solve_first_enigma' => true, // Premier succès automatique
                'solve_without_hints' => $enigma->userProgress->hints_used === 0,
                'solve_under_time' => $enigma->userProgress->time_spent < ($requirements['time'] ?? 300),
                'solve_streak' => $this->checkSolveStreak($user, $requirements['count'] ?? 3),
                default => false
            };

            if ($shouldUnlock) {
                $user->achievements()->attach($achievement->id, [
                    'unlocked_at' => now()
                ]);

                // Ajouter les points du succès
                $user->points += $achievement->points_reward;
                $user->save();
            }
        }
    }

    private function checkSolveStreak($user, $requiredCount)
    {
        $latestProgress = UserProgress::where('user_id', $user->id)
            ->where('completed', true)
            ->orderBy('completed_at', 'desc')
            ->take($requiredCount)
            ->get();

        if ($latestProgress->count() < $requiredCount) {
            return false;
        }

        $previousTime = null;
        foreach ($latestProgress as $progress) {
            if ($previousTime && $progress->completed_at->diffInHours($previousTime) > 24) {
                return false;
            }
            $previousTime = $progress->completed_at;
        }

        return true;
    }

    public function hint(Enigma $enigma)
    {
        $userProgress = UserProgress::firstOrNew([
            'user_id' => Auth::id(),
            'enigma_id' => $enigma->id
        ]);

        $userProgress->hints_used = ($userProgress->hints_used ?? 0) + 1;
        $userProgress->save();

        return response()->json([
            'hint' => $enigma->hint
        ]);
    }
}
